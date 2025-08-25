<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class Member extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'uuid',
        'pin',
        'firebase_uid',
        'phone_number',
        'email',
        'full_name',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'id_type',
        'id_number',
        'id_document_path',
        'photo_path',
        'country',
        'region',
        'district',
        'ward',
        'street',
        'postal_code',
        'latitude',
        'longitude',
        'shop_name',
        'shop_description',
        'shop_logo',
        'seller_id',
        'upline_id',
        'seller_level',
        'referral_code',
        'referred_by_code',
        'referral_bonus_eligible',
        'total_downlines',
        'total_sales_volume',
        'commission_balance',
        'last_commission_paid_at',
        'last_login_at',
        'last_active_at',
        'is_team_leader',
        'is_blacklisted',
        'joined_via_invite',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'mobile_money_number',
        'kyc_verified',
        'account_status',
        'api_token',
        'sponsor_code',
        'fcm_token',
        'wallet_balance'
    ];

    protected $hidden = [
        'pin',
        'api_token',
    ];

    protected $casts = [
        'kyc_verified' => 'boolean',
        'is_team_leader' => 'boolean',
        'is_blacklisted' => 'boolean',
        'referral_bonus_eligible' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($member) {
            if (empty($member->uuid)) {
                $member->uuid = Str::uuid()->toString();
            }
        });
    }

    // Relationships
    public function sponsor()
    {
        return $this->belongsTo(Member::class, 'upline_id', 'seller_id');
    }

    public function downlines()
    {
        return $this->hasMany(Member::class, 'upline_id', 'seller_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }
}
