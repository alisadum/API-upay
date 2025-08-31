<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'title',
        'description',
        'original_price',
        'discount_percent',
        'price',
        'terms_conditions',
        'location',
        'outlet_id', // Deprecated, tapi biarin untuk backward compatibility
        'category_id',
        'start_time',
        'end_time',
        'available_seats',
        'photo_path',
        'is_approved',
        'promo_type',
        'buy_quantity',
        'free_quantity',
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

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

     public function outlets()
    {
        return $this->belongsToMany(Outlet::class, 'promotion_outlet', 'promotion_id', 'outlet_id');
    }

    // Hitung harga final berdasarkan promo_type
    public function getFinalPriceAttribute()
    {
        if ($this->promo_type === 'buy_get_free') {
            // Harga untuk buy_quantity item, free_quantity gratis
            return $this->original_price * $this->buy_quantity;
        }
        // Default: diskon persen
        return $this->original_price - ($this->original_price * $this->discount_percent / 100);
    }
}
