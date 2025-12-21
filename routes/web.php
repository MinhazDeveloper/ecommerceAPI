<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/bkash/callback', [PaymentController::class, 'bkashCallback'])->name('bkash.callback');
