<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Subscription extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'key',
        'max_devices',
        'devices_used',
        'type',
        'expires_at',
        'verification_key',
        'is_active'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'devices_used' => 'integer'
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public static function generateVerificationKey()
    {
        return Str::uuid()->toString();
    }

    public static function calculateExpiration($type)
    {
        switch ($type) {
            case '1month':
                return now()->addMonth();
            case '3months':
                return now()->addMonths(3);
            case 'lifetime':
                return now()->addYear(3);
            default:
                return now()->addMonth();
        }
    }
    public function canActivate()
    {
        return $this->is_active &&
            ($this->max_devices > $this->devices_used) &&
            (!$this->expires_at || $this->expires_at->gt(now()));
    }

    public function incrementUsage()
    {
        $this->devices_used++;
        if ($this->devices_used >= $this->max_devices) {
            $this->is_active = false;
        }
        $this->save();
    }
}
