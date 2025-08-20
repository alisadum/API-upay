<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $merchant_id
 * @property string $title
 * @property string $description
 * @property int $price
 * @property int $is_approved
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Merchant $merchant
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Voucher> $vouchers
 * @property-read int|null $vouchers_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereIsApproved($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereMerchantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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