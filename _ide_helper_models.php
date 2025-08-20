<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $business_name
 * @property string $address
 * @property int $is_approved
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Promotion> $promotions
 * @property-read int|null $promotions_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchant whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchant whereBusinessName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchant whereIsApproved($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchant whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchant whereUserId($value)
 * @mixin \Eloquent
 */
	class Merchant extends \Eloquent {}
}

namespace App\Models{
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
	class Promotion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Merchant|null $merchant
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Voucher> $vouchers
 * @property-read int|null $vouchers_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 * @mixin \Eloquent
 */
	class User extends \Eloquent {}
}

namespace App\Models{
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
	class Voucher extends \Eloquent {}
}

