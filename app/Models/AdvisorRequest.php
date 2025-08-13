<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdvisorRequest extends Model
{
    protected $fillable = [
        'user_id',
        'advisor_id',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function advisor()
    {
        return $this->belongsTo(Advisor::class);
    }
}

