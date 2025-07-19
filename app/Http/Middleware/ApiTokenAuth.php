<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Models\Device;

class ApiTokenAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Try to get token from Authorization header or query parameter
        $token = $request->bearerToken() ?: $request->query('api_token');

        if (!$token) {
            return response()->json([
                'error' => 'API token required',
                'docs' => 'Include Authorization: Bearer <token> or ?api_token=<token>'
            ], 401);
        }

        try {
            $device = Device::where('api_token', $token)->first();
            if (! $device) {
                return response()->json([
                    'error' => 'Invalid API token',
                    'docs' => 'Include Authorization: Bearer <token> or ?api_token=<token>'
                ], 401);
            }

            // Update last_active_at
            $device->update(['last_active_at' => now()]);
            $device->update(['appversion' =>'1.1']);

            // Check if VIP has expired
            if ($device->is_vip && $device->vip_expires_at && now()->gt($device->vip_expires_at)) {
                $device->update([
                    'is_vip' => false,
                    'vip_expires_at' => null
                ]);
            }

            $request->merge(['device' => $device]);
            return $next($request);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
