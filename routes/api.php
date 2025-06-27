<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/db-con', function () {
    try {
        $dbconnect = DB::connection()->getPDO();
        //test
        $dbname = DB::connection()->getDatabaseName();
        echo "Connected successfully to the database. Database name is :" . $dbname;
    } catch (Exception $e) {
        echo "Error in connecting to the database" . $e->getMessage();
    }
});