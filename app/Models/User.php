<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    /**
     * Chaîne d'inscription hiérarchique:
     * admin → ministere → provincial → communal → zonal → collinaire → citoyen
     */
    const ROLE_HIERARCHY = [
        'admin'      => 'ministere',
        'ministere'  => 'provincial',
        'provincial' => 'communal',
        'communal'   => 'zonal',
        'zonal'      => 'collinaire',
        'collinaire' => 'citoyen',
    ];

    const ALL_ROLES = ['citoyen', 'collinaire', 'zonal', 'communal', 'provincial', 'ministere', 'admin', 'police', 'agent_recensement'];

    const AUTHORITY_ROLES = ['collinaire', 'zonal', 'communal', 'provincial', 'ministere', 'admin'];

    /**
     * Niveau géographique attendu par rôle.
     * null = pas de restriction géographique (voit tout le pays).
     */
    const ROLE_GEO_LEVEL = [
        'admin'      => null,
        'ministere'  => null,
        'provincial' => 'province',
        'communal'   => 'commune',
        'zonal'      => 'zone',
        'collinaire' => 'colline',
        'citoyen'    => 'colline',
        'police'              => null,
        'agent_recensement'   => null,
    ];

    protected $fillable = [
        'nom',
        'role',
        'telephone',
        'email',
        'password',
        'photo_profil',
        'created_by',
        'geographic_area_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    // --- Relations ---

    public function household()
    {
        return $this->hasOne(Household::class, 'chef_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function announcements()
    {
        return $this->hasMany(Announcement::class, 'author_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'citizen_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdUsers()
    {
        return $this->hasMany(User::class, 'created_by');
    }

    public function geographicArea()
    {
        return $this->belongsTo(GeographicArea::class, 'geographic_area_id');
    }

    public function censusAssignments()
    {
        return $this->hasMany(CensusAgent::class, 'user_id');
    }

    // --- Méthodes de rôle ---

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAuthority(): bool
    {
        return in_array($this->role, self::AUTHORITY_ROLES);
    }

    /**
     * Le rôle que cet utilisateur peut inscrire.
     * Retourne null si ce rôle ne peut inscrire personne.
     */
    public function getSubordinateRole(): ?string
    {
        return self::ROLE_HIERARCHY[$this->role] ?? null;
    }

    /**
     * Vérifie si cet utilisateur peut inscrire un utilisateur avec le rôle donné.
     */
    public function canRegister(string $role): bool
    {
        if ($this->role === 'admin' && $role === 'police') {
            return true;
        }

        return $this->getSubordinateRole() === $role;
    }

    /**
     * Les rôles autorisés que cet utilisateur peut créer.
     */
    public function getCreatableRoles(): array
    {
        $roles = [];
        $sub = $this->getSubordinateRole();
        if ($sub) {
            $roles[] = $sub;
        }
        if ($this->role === 'admin') {
            $roles[] = 'police';
        }
        return $roles;
    }

    // --- Méthodes de zone ---

    /**
     * Retourne les IDs de toutes les zones accessibles par cet utilisateur.
     * null = pas de filtre (tout est visible).
     */
    public function getAccessibleAreaIds(): ?array
    {
        if ($this->isAdmin() || !$this->geographic_area_id) {
            return null;
        }

        return $this->geographicArea->getDescendantIds();
    }
}
