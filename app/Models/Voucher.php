<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $promotion_id
 * @property string $code
 * @property bool $is_redeemed
 * @property \Illuminate\Support\Carbon|null $redeemed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Promotion $promotion
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereIsRedeemed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher wherePromotionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereRedeemedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereUserId($value)
 * @mixin \Eloquent
 */
class Voucher extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'promotion_id', 'code', 'is_redeemed', 'redeemed_at'];

    protected $casts = [
        'is_redeemed' => 'boolean',
        'redeemed_at' => 'datetime',
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