<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CensusField extends Model
{
    protected $fillable = [
        'census_id',
        'label',
        'type',
        'options',
        'required',
        'field_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'required' => 'boolean',
        ];
    }

    public function census()
    {
        return $this->belongsTo(Census::class);
    }

    public function values()
    {
        return $this->hasMany(CensusResponseValue::class, 'field_id');
    }
}
