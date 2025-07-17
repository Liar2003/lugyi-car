<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceReferral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_device_id',
        'referred_device_id',
        'referred_at',
    ];

    public function referrer()
    {
        return $this->belongsTo(Device::class, 'referrer_device_id');
    }

    public function referred()
    {
        return $this->belongsTo(Device::class, 'referred_device_id');
    }
}
