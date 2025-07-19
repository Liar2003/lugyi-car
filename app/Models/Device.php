<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Device extends Model
{
    /**
     * Log subscription usage and grant reward if milestone reached
     */
    public function useSubscription($subscriptionKey)
    {
        $history = DeviceSubscriptionHistory::create([
            'device_id' => $this->id,
            'subscription_key' => $subscriptionKey,
            'used_at' => now(),
        ]);
        // // Get subscription type
        // $subscription = $this->subscription;
        // if (! $subscription || ! $subscription->is_active) {
        //     return $history;
        // }

        // $type = $subscription->type;
        // $milestone = null;
        // $rewardDays = null;
        // if ($type === '1month') {
        //     $milestone = 5;
        //     $rewardDays = 7; // Example: add 7 days for milestone
        // } elseif ($type === '3months') {
        //     $milestone = 20;
        //     $rewardDays = 30; // Example: add 30 days for milestone
        // }

        // if ($milestone) {
        //     // Count unique days used for this subscription key by this device
        //     $daysUsed = DeviceSubscriptionHistory::where('device_id', $this->id)
        //         ->where('subscription_key', $subscriptionKey)
        //         ->selectRaw('DATE(used_at) as day')
        //         ->groupBy('day')
        //         ->get()->count();

        //     if ($daysUsed > 0 && $daysUsed % $milestone === 0) {
        //         // Grant reward: extend subscription
        //         $subscription->expires_at = $subscription->expires_at->addDays($rewardDays);
        //         $subscription->save();
        //         $history->reward_granted = true;
        //         $history->save();
        //     }
        // }
        return $history;
    }
    //
    use HasFactory;

    protected $fillable = [
        'device_id',
        'api_token',
        'user_identifier',
        'device_token',
        'platform',
        'osversion',
        'appversion',
        'is_vip',
        'vip_expires_at',
        'subscription_key',
        'last_active_at',
        'referral_claimed_count',
        'vip_referred_claimed_count',
        'normal_referred_claimed_count',
    ];

    protected $casts = [
        'is_vip' => 'boolean',
        'vip_expires_at' => 'datetime',
        'last_active_at' => 'datetime',
        'referral_claimed_count' => 'integer',
        'vip_referred_claimed_count' => 'integer',
        'normal_referred_claimed_count' => 'integer',
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
