<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Device extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'device_id',
        'api_token',
        'user_identifier',
        'device_token',
        'platform',
        'os_version',
        'app_version',
        'is_vip',
        'vip_expires_at',
        'subscription_key',
        'last_active_at'
    ];

    protected $casts = [
        'is_vip' => 'boolean',
        'vip_expires_at' => 'datetime',
        'last_active_at' => 'datetime'
    ];
    protected $hidden = [
        'api_token',
        'device_token',
        'user_identifier',
        'subscription_key'
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_identifier', 'email');
    }

    public function views()
    {
        return $this->hasMany(ContentView::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription_key', 'key');
    }
    public static function generateToken()
    {
        return bin2hex(random_bytes(32));
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('expires_at', '>', now())
                    ->orWhere('type', 'lifetime');
            });
    }

    public function isVip()
    {
        return $this->is_vip && (
            $this->vip_expires_at === null ||
            $this->vip_expires_at > now()
        );
    }
    public function scopeActive($query)
    {
        return $query->where('last_active_at', '>', now()->subDays(30));
    }

    public function scopeVip($query)
    {
        return $query->where('is_vip', true)
            ->where(function ($q) {
                $q->whereNull('vip_expires_at')
                    ->orWhere('vip_expires_at', '>', now());
            });
    }
}
