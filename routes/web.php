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
Route::get('/contents', function () {
    return view('contents');
});
Route::get('/devices', function () {
    return view('devices');
});
Route::get('/live-sport', function () {
    return view('live-sport');
});
Route::get('/dashboard', function () {
    return view('welcome');
});
