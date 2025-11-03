<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\WishlistController;

// -----------------------
// Public routes (no auth)
// -----------------------
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

//Add to cart
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']); 
    Route::post('/', [CartController::class, 'store']); 
    Route::delete('/{productId}', [CartController::class, 'destroy']); 
});

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
    //add cart & wishlist
    
    //WishList route
    Route::prefix('wishlist')->group(function () {
        Route::get('/', [WishlistController::class, 'index']); 
        Route::post('/', [WishlistController::class, 'store']); 
        // DELETE: /api/wishlist/{productId} (Remove from Wishlist)
        Route::delete('/{productId}', [WishlistController::class, 'destroy']); 
    });
});

 
