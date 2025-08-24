<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'merchant_id',
        'title',
        'description',
        'price',
        'original_price',
        'terms_conditions',
        'location',
        'category',
        'start_time',
        'end_time',
        'photo_path',
        'is_approved',
        'reject_reason',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_approved' => 'boolean',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }
}