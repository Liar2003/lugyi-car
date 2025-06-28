<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //
    public function normalEndpoint()
    {
        return response()->json(['message' => 'Normal access granted']);
    }

    public function vipEndpoint()
    {
        return response()->json(['message' => 'VIP access granted']);
    }
}
