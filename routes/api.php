<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;

// -----------------------
// Public routes (no auth)
// -----------------------
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// -----------------------
// Protected routes (auth:sanctum required)
// -----------------------
Route::middleware('auth:sanctum')->group(function () {

    // Current authenticated user info
    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });

    // Auth actions
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // -----------------------
    // Role based routes
    // -----------------------
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', function () {
            return response()->json(['message' => 'Admin only content']);
        });
    });

    // Resource routes
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);
    
});

 
