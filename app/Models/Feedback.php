<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $fillable = ['report_id', 'citizen_id', 'rating', 'comment'];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function citizen()
    {
        return $this->belongsTo(User::class, 'citizen_id');
    }
}
