<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CensusResponse extends Model
{
    protected $fillable = [
        'census_id',
        'agent_id',
        'geographic_area_id',
        'respondent_name',
        'respondent_phone',
        'latitude',
        'longitude',
    ];

    public function census()
    {
        return $this->belongsTo(Census::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function geographicArea()
    {
        return $this->belongsTo(GeographicArea::class, 'geographic_area_id');
    }

    public function values()
    {
        return $this->hasMany(CensusResponseValue::class, 'response_id');
    }
}
