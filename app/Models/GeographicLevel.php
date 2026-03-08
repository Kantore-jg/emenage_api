<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeographicLevel extends Model
{
    protected $fillable = ['name', 'slug', 'level_order'];

    public function areas()
    {
        return $this->hasMany(GeographicArea::class, 'level_id');
    }
}
