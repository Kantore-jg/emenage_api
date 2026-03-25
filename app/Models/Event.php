<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'titre', 'description', 'date_debut', 'date_fin',
        'lieu', 'type', 'created_by', 'geographic_area_id', 'announcement_id',
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
        return $this->belongsTo(GeographicArea::class);
    }

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }
}
