<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/privacy', function () {
    return view('privacy');
});
Route::get('/claim-reward', function () {
    return view('claim-reward');
});
