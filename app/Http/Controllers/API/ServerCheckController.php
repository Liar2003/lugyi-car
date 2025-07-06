<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ServerCheckController extends Controller
{
    //
    public function check(Request $request): JsonResponse
    {
        // If the app has been put into maintenance mode (php artisan down)
        if (app()->isDownForMaintenance()) {
            return response()->json([
                'status'  => 'maintenance',
                'message' => 'Service is temporarily under maintenance. Please try again later.'
            ], 503);
        }

        // You can also add other health checks here (DB, cache, etc.)
        return response()->json([
            'status'    => 'ok',
            'timestamp' => now()->toDateTimeString(),
        ], 200);
    }
    public function tempDown(Request $request): JsonResponse
    {


        // You can also add other health checks here (DB, cache, etc.)
        return response()->json([
            'status'  => 'maintenance',
            'message' => 'Service is temporarily under maintenance. Please try again later.'
        ], 503);
    }
}
