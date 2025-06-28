<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use App\Models\Device;
class VipAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var Device $device */
        $device = $request->device;
        
        // Check VIP status considering expiration
        if (!$device->isVip()) {
            return response()->json([
                'error' => 'VIP access required',
                'upgrade_url' => '/api/upgrade-info',
                'subscription_status' => $this->getSubscriptionStatus($device)
            ], 403);
        }

        return $next($request);
    }
    
    private function getSubscriptionStatus(Device $device)
    {
        $activeSubscription = $device->activeSubscription;
        $isVip = $device->isVip();
        $expiryInfo = null;
        
        if ($isVip && $device->vip_expires_at) {
            $expiresAt = Carbon::parse($device->vip_expires_at);
            $expiryInfo = [
                'expires_at' => $expiresAt->toDateTimeString(),
                'days_remaining' => max(0, $expiresAt->diffInDays(now())) . ' days',
                'is_expired' => $expiresAt->isPast()
            ];
        }
        
        return [
            'is_vip' => $isVip,
            'vip_expires_at' => $device->vip_expires_at,
            'expiry_info' => $expiryInfo,
            'active_subscription' => $activeSubscription ? [
                'type' => $activeSubscription->type,
                'expires_at' => $activeSubscription->expires_at,
                'is_lifetime' => $activeSubscription->type === 'lifetime'
            ] : null
        ];
    }
}
