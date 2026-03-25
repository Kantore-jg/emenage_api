<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'citizen_id',
        'description',
        'latitude',
        'longitude',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    public function citizen()
    {
        return $this->belongsTo(User::class, 'citizen_id');
    }

    public function photos()
    {
        return $this->hasMany(ReportPhoto::class);
    }

    public function feedback()
    {
        return $this->hasOne(Feedback::class);
    }
}
