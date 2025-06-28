<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdminAuthController extends Controller
{
    //
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'User registered successfully'], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        return $this->createTokenResponse($user);
    }

    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required'
        ]);

        $refreshToken = $request->refresh_token;
        $token = UserToken::where('refresh_token', $refreshToken)
            ->where('refresh_expires_at', '>', Carbon::now())
            ->first();

        if (!$token) {
            return response()->json(['error' => 'Invalid or expired refresh token'], 401);
        }

        $token->delete();
        return $this->createTokenResponse($token->user);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out from all devices']);
    }

    protected function createTokenResponse(User $user)
    {
        // Delete expired tokens
        UserToken::where('access_expires_at', '<', Carbon::now())
            ->orWhere('refresh_expires_at', '<', Carbon::now())
            ->delete();

        // Generate new tokens
        $accessToken = Str::random(64);
        $refreshToken = Str::random(64);

        // Create token record
        $token = $user->tokens()->create([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'access_expires_at' => Carbon::now()->addWeek(),
            'refresh_expires_at' => Carbon::now()->addMonth(),
        ]);

        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'access_expires_in' => 604800, // 1 week in seconds
            'refresh_expires_in' => 2592000, // 1 month in seconds
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]
        ]);
    }
}
