<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $fillable = ['nombre'];

    public function advisors()
    {
        return $this->hasMany(Advisor::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }


}
