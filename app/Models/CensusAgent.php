<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CensusAgent extends Model
{
    protected $fillable = [
        'census_id',
        'user_id',
        'geographic_area_id',
    ];

    public function census()
    {
        return $this->belongsTo(Census::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function geographicArea()
    {
        return $this->belongsTo(GeographicArea::class, 'geographic_area_id');
    }
}
