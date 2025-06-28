<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ContentView;
class TrackContentView
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        // Only track GET requests for content details
        if ($request->isMethod('GET') && $request->route()->getName() === 'contents') {
            $content = $request->route();
            $device = $request->device ?? null;

            ContentView::create([
                'content_id' => $content->id,
                'device_id' => $device ? $device->id : null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
        }

        return $response;
    }
}
