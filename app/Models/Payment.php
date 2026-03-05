<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'household_id',
        'motif',
        'motif_autre',
        'montant',
        'date_paiement',
        'recu_photo',
        'statut_validation',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_paiement' => 'date',
        ];
    }

    public function household()
    {
        return $this->belongsTo(Household::class);
    }
}
