<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//payment integration(bkash callback)
Route::get('/bkash/callback', [PaymentController::class, 'callback'])
    ->name('bkash.callback');
