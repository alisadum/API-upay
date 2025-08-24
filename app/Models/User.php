<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'points',
        'membership_level',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'points' => 'integer',
            'balance' => 'decimal:2',
        ];
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class);
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    public function pointLogs()
    {
        return $this->hasMany(PointLog::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function userWallet(): HasOne
    {
        return $this->hasOne(UserWallet::class);
    }

    
    public function wallet()
    {
        return $this->userWallet();
    }

    public function updateMembershipLevel()
    {
        $this->membership_level = $this->points > 100 ? 'gold' : 'silver';
    }

    public function save(array $options = [])
    {
        $this->updateMembershipLevel();
        return parent::save($options);
    }
}