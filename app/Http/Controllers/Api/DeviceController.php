<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DeviceController extends Controller
{
    //
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:255',
            'platform' => 'required|string|in:android,ios,web,desktop',
            'os_version' => 'nullable|string|max:50',
            'app_version' => 'nullable|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if device already exists
        $device = Device::where('device_id', $request->device_id)->first();

        if ($device) {
            // Update last_active_at or other info if needed
            $device->update([
                'platform' => $request->platform,
                'osversion' => $request->os_version,
                'appversion' => $request->app_version,
                'last_active_at' => now()
            ]);

            return response()->json([
                'device_id' => $device->device_id,
                'api_token' => $device->api_token,
                'platform' => $device->platform,
                'is_vip' => $device->is_vip ?? false,
                'vip_expires_at' => $device->vip_expires_at ?? null
            ], 200);
        }

        // Create new device if not found
        $device = Device::create([
            'device_id' => $request->device_id,
            'api_token' => Device::generateToken(),
            'platform' => $request->platform,
            'osversion' => $request->os_version,
            'appversion' => $request->app_version,
            'last_active_at' => now()
        ]);

        return response()->json([
            'device_id' => $device->device_id,
            'api_token' => $device->api_token,
            'platform' => $device->platform,
            'is_vip' => false,
            'vip_expires_at' => null
        ], 201);
    }


    public function linkToUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_identifier' => 'required|email|exists:users,email',
            'device_token' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $device = $request->device;
        $user = User::where('email', $request->user_identifier)->first();

        // Check device limit (max 2 per user)
        $activeDevices = Device::where('user_identifier', $request->user_identifier)
            ->where('is_vip', true)
            ->count();

        if ($activeDevices >= 2) {
            return response()->json([
                'error' => 'Maximum of 2 VIP devices per account',
                'max_devices' => 2,
                'contact' => 'support@example.com'
            ], 403);
        }

        $device->update([
            'user_identifier' => $request->user_identifier,
            'device_token' => $request->device_token ?? Str::random(64),
            'last_active_at' => now()
        ]);

        return response()->json([
            'message' => 'Device linked successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ],
            'device' => [
                'id' => $device->id,
                'device_id' => $device->device_id,
                'platform' => $device->platform
            ]
        ]);
    }

    public function status(Request $request)
    {
        $device = $request->device;
        $device->update(['last_active_at' => now()]);

        return response()->json([
            'device_id' => $device->device_id,
            'user_identifier' => $device->user_identifier,
            'is_linked' => !!$device->user_identifier,
            'is_vip' => $device->isVip(),
            'vip_expires_at' => $device->vip_expires_at,
            'platform' => $device->platform,
            'osversion' => $device->os_version,
            'appversion' => $device->app_version,
            'last_active' => $device->last_active_at,
            'subscription_status' => $device->subscription ? [
                'key' => $device->subscription->key,
                'type' => $device->subscription->type,
                'expires_at' => $device->subscription->expires_at
            ] : null
        ]);
    }

    public function unlink(Request $request)
    {
        $device = $request->device;

        if (!$device->user_identifier) {
            return response()->json(['error' => 'Device not linked to any account'], 400);
        }

        $device->update([
            'user_identifier' => null,
            'device_token' => null,
            'is_vip' => false,
            'vip_expires_at' => null,
            'subscription_key' => null,
            'last_active_at' => now()
        ]);

        return response()->json(['message' => 'Device unlinked successfully']);
    }

    public function update(Request $request, $device_id)
    {
        $validator = Validator::make($request->all(), [
            'osversion' => 'nullable|string|max:50',
            'appversion' => 'nullable|string|max:50',
            'vip_expires_at' => 'nullable|string|max:100',
            'is_vip' => 'nullable|boolean',
            'platform' => 'nullable|string|in:android,ios,web,desktop'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $device = Device::where('device_id', $device_id)->firstOrFail();
        $device->osversion = $request->osversion ?? $device->osversion;
        $device->appversion = $request->appversion ?? $device->appversion;
        $device->vip_expires_at = $request->vip_expires_at ? Carbon::parse($request->vip_expires_at) : $device->vip_expires_at;
        $device->is_vip = $request->has('is_vip') ? (bool)$request->is_vip : $device->is_vip;
        $device->platform = $request->platform ?? $device->platform;
        $device->last_active_at = now();
        // Update the device with the validated data
        $device->update($request->all());
        // $device->update([
        //     'osversion' => $request->osversion ?? $device->osversion,
        //     'appversion' => $request->appversion ?? $device->appversion,
        //     'vip_expires_at' => $request->vip_expires_at ?? $device->vip_expires_at,
        //     'is_vip' => $request->has('is_vip') ? (bool)$request->is_vip : $device->is_vip,
        //     'platform' => $request->platform ?? $device->platform,
        //     'last_active_at' => now()
        // ]);

        return response()->json([
            'message' => 'Device updated successfully',
            'device' => [
                'id' => $device->id,
                'device_id' => $device->device_id,
                'platform' => $device->platform,
                'osversion' => $device->osversion,
                'appversion' => $device->appversion
            ]
        ]);
    }
}
