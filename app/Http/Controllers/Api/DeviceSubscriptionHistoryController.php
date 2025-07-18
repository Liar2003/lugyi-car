<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceSubscriptionHistory;
use App\Models\Subscription;

class DeviceSubscriptionHistoryController extends Controller
{

    public function claimRewardBySubscriptionKey(Request $request)
    {
        $subscription_key = $request->input('subscription_key');
        $device = $request->device;
        if (! $device) {
            return response()->json(['message' => 'Device not found'], 404);
        }

        $subscriptionHistory = DeviceSubscriptionHistory::where('device_id', $device->id)
            ->where('subscription_key', $subscription_key)
            ->first();
        if (!$subscriptionHistory) {
            return response()->json(['message' => "You don't have this subscription key!"]);
        }

        $subscription = Subscription::where('key', $subscription_key)->first();
        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        $type = $subscription->type;
        $milestone = null;
        $extendDays = null;

        // Define milestones and extension days based on type
        switch ($type) {
            case '1month':
                $milestone = 5;
                $extendDays = 7;
                break;
            case '3months':
                $milestone = 20;
                $extendDays = 20;
                break;
            case '5months':
                $milestone = 35;
                $extendDays = 35;
                break;
            case '8months':
                $milestone = 55;
                $extendDays = 50;
                break;
            case '1year':
                $milestone = 80;
                $extendDays = 70;
                break;
        }

        if (! $milestone || ! $extendDays) {
            return response()->json(['message' => 'Unsupported subscription type'], 400);
        }

        // Check if reward already granted for this milestone
        $rewardAlreadyGranted = DeviceSubscriptionHistory::where('device_id', $device->id)
            ->where('subscription_key', $subscription_key)
            ->where('reward_granted', true)
            ->exists();

        if ($rewardAlreadyGranted) {
            return response()->json(['message' => 'Reward already claimed for this subscription key'], 409);
        }

        // Extend VIP expiration date
        $device->vip_expires_at = $device->vip_expires_at
            ? $device->vip_expires_at->addDays($extendDays)
            : now()->addDays($extendDays);

        $device->is_vip = true;
        $device->save();

        // Mark reward as granted in history
        DeviceSubscriptionHistory::where('device_id', $device->id)
            ->where('subscription_key', $subscription_key)
            ->update(['reward_granted' => true]);

        return response()->json([
            'message' => 'Reward claimed and VIP expiration extended',
            'vip_expires_at' => $device->vip_expires_at,
            'extend_days' => $extendDays,
        ]);
    }


    // Get only rewards for a device
    public function rewards(Request $request, $device_id)
    {
        $device = \App\Models\Device::where('device_id', $device_id)->first();
        if (! $device) {
            return response()->json(['error' => 'Device not found'], 404);
        }
        $rewards = \App\Models\DeviceSubscriptionHistory::where('device_id', $device->id)
            ->where('reward_granted', true)
            ->with('subscription')
            ->orderBy('used_at', 'desc')
            ->get();
        return response()->json([
            'device_id' => $device->device_id,
            'rewards' => $rewards,
        ]);
    }
    // Get subscription usage history and rewards for a device
    public function history(Request $request)
    {
        $device = $request->device;
        if (! $device) {
            return response()->json(['error' => 'Device not found'], 404);
        }
        $history = DeviceSubscriptionHistory::where('device_id', $device->id)
            ->with('subscription')
            ->orderBy('used_at', 'desc')
            ->get();
        $rewards = $history->where('reward_granted', true);
        return response()->json([
            'device_id' => $device->device_id,
            'history' => $history,
            'rewards' => $rewards,
        ]);
    }
}
