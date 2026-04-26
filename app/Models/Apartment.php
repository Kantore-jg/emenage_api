<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apartment extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'geographic_area_id',
        'avenue',
        'numero',
        'description',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function geographicArea()
    {
        return $this->belongsTo(GeographicArea::class, 'geographic_area_id');
    }

    public function households()
    {
        return $this->hasMany(Household::class, 'apartment_id');
    }

    /**
     * Scope: filtrer les appartements visibles par un utilisateur selon sa zone.
     */
    public function scopeForUserZone($query, User $user)
    {
        $areaIds = $user->getAccessibleAreaIds();

        if ($areaIds === null) {
            return $query;
        }

        return $query->whereIn('apartments.geographic_area_id', $areaIds);
    }

    /**
     * Adresse complète formatée.
     */
    public function getFullAddressAttribute(): string
    {
        $area = $this->geographicArea;
        $path = $area ? $area->full_path : '';
        return "{$path}, Avenue {$this->avenue}, N°{$this->numero}";
    }
}
