<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'sender',
        'session_id',
        'intencion',
        'area',
        'advisor_id',
    ];

    public function advisor()
    {
        return $this->belongsTo(Advisor::class);
    }
}
