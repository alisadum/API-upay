<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'user_id',
        'promotion_id',
        'code',
        'payment_method',
        'qr_path',
        'proof_path',
        'booking_date',
        'booking_time',
        'status',
        'is_redeemed',
        'is_paid',
        'transaction_id',
        'redeemed_at',
    ];

    protected $casts = [
        'is_redeemed' => 'boolean',
        'is_paid' => 'boolean',
        'redeemed_at' => 'datetime',
        'booking_date' => 'date',
        'booking_time' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }
}