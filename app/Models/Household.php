<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Household extends Model
{
    use HasFactory;

    protected $fillable = [
        'chef_id',
        'quartier',
        'adresse',
        'geographic_area_id',
    ];

    public function chef()
    {
        return $this->belongsTo(User::class, 'chef_id');
    }

    public function geographicArea()
    {
        return $this->belongsTo(GeographicArea::class, 'geographic_area_id');
    }

    public function members()
    {
        return $this->hasMany(Member::class);
    }

    public function permanentMembers()
    {
        return $this->hasMany(Member::class)->where('type', 'permanent');
    }

    public function invites()
    {
        return $this->hasMany(Member::class)->where('type', 'invite');
    }

    public function presentInvites()
    {
        return $this->hasMany(Member::class)->where('type', 'invite')->where('statut', 'present');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scope: filtrer les ménages visibles par un utilisateur selon sa zone.
     */
    public function scopeForUserZone($query, User $user)
    {
        $areaIds = $user->getAccessibleAreaIds();

        if ($areaIds === null) {
            return $query;
        }

        return $query->whereIn('households.geographic_area_id', $areaIds);
    }
}
