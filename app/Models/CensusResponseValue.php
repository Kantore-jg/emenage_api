<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CensusResponseValue extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'response_id',
        'field_id',
        'value',
    ];

    public function response()
    {
        return $this->belongsTo(CensusResponse::class, 'response_id');
    }

    public function field()
    {
        return $this->belongsTo(CensusField::class, 'field_id');
    }
}
