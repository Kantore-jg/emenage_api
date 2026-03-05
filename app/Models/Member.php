<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'household_id',
        'nom',
        'type',
        'statut',
        'statut_validation',
        'photo_cni',
        'age',
        'telephone',
    ];

    public function household()
    {
        return $this->belongsTo(Household::class);
    }
}
