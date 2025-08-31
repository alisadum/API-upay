<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Outlet extends Model
{
    protected $fillable = [
        'merchant_id',
        'name',
        'address',
        'city',
        'province',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

   public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'promotion_outlet', 'outlet_id', 'promotion_id');
    }
}