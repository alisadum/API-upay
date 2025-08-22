<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Promotion extends Model
{
    use HasFactory;

    protected $fillable = ['merchant_id', 'title', 'description', 'price', 'is_approved'];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }
}
