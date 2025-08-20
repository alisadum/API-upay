<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'business_name', 'address', 'is_approved'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function promotions()
    {
        return $this->hasMany(Promotion::class);
    }
}