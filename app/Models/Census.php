<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Census extends Model
{
    protected $table = 'censuses';

    protected $fillable = [
        'titre',
        'description',
        'statut',
        'date_debut',
        'date_fin',
        'geographic_area_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function geographicArea()
    {
        return $this->belongsTo(GeographicArea::class, 'geographic_area_id');
    }

    public function fields()
    {
        return $this->hasMany(CensusField::class)->orderBy('field_order');
    }

    public function agents()
    {
        return $this->hasMany(CensusAgent::class);
    }

    public function responses()
    {
        return $this->hasMany(CensusResponse::class);
    }

    public function isActive(): bool
    {
        return $this->statut === 'actif';
    }
}
