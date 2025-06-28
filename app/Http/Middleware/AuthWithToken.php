<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\UserToken;
use Carbon\Carbon;
class AuthWithToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $accessToken = $request->bearerToken();
        
        if (!$accessToken) {
            return response()->json(['error' => 'Access token required'], 401);
        }

        $token = UserToken::where('access_token', $accessToken)
            ->where('access_expires_at', '>', Carbon::now())
            ->with('user')
            ->first();

        if (!$token) {
            return response()->json(['error' => 'Invalid or expired access token'], 401);
        }

        Auth::login($token->user);
        return $next($request);;
    }
}
