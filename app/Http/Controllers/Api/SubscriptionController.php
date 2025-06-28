<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Device;

class SubscriptionController extends Controller
{
    //
    public function verifyKey(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subscription_key' => 'required|string|exists:subscriptions,key',
            'device_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $subscription = Subscription::where('key', $request->subscription_key)->first();

        // Check if subscription can be activated
        if (!$subscription->canActivate()) {
            return response()->json([
                'error' => 'Subscription key limit reached or expired',
                'max_devices' => $subscription->max_devices,
                'devices_used' => $subscription->devices_used,
                'is_active' => $subscription->is_active
            ], 400);
        }

        // Check if device already used this key
        $device = Device::where('device_id', $request->device_id)->first();
        if ($device->subscription_key === $request->subscription_key) {
            return response()->json(['error' => 'Device already activated with this key'], 400);
        }

        // Activate VIP for device
        $device->update([
            'is_vip' => true,
            'vip_expires_at' => $subscription->expires_at,
            'subscription_key' => $request->subscription_key
        ]);

        // Increment key usage
        $subscription->incrementUsage();

        return response()->json([
            'message' => 'VIP subscription activated!',
            'type' => $subscription->type,
            'expires_at' => $subscription->expires_at,
            'devices_remaining' => $subscription->max_devices - $subscription->devices_used
        ]);
    }

    public function subscriptionStatus(Request $request)
    {
        $device = $request->device;
        $subscription = $device->subscription;

        return response()->json([
            'is_vip' => $device->isVip(),
            'vip_expires_at' => $device->vip_expires_at,
            'subscription_key' => $device->subscription_key,
            'subscription_status' => $device->subscription ? [
                'key' => $subscription->key,
                'type' => $subscription->type,
                'max_devices' => $subscription->max_devices,
                'devices_used' => $subscription->devices_used,
                'is_active' => $subscription->is_active
            ] : null
        ]);
    }
}
