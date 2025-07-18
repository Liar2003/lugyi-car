<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeviceSubscriptionHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'subscription_key',
        'used_at',
        'reward_granted',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'reward_granted' => 'boolean',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription_key', 'key');
    }
}
