<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Device;
class AuthController extends Controller
{
    //
    public function register(Request $request)
    {
        $request->validate(['device_id' => 'required|string|max:255']);

        $device = Device::firstOrCreate(
            ['device_id' => $request->device_id],
            ['api_token' => Device::generateToken()]
        );

        return response()->json([
            'api_token' => $device->api_token
        ], 201);
    }
}
