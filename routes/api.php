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
            return response()->json(['message' => 'Welcome Admin dashboard']);
        });
    });

    // শুধুমাত্র Vendor এর জন্য
    Route::middleware('role:vendor')->group(function () {
        Route::get('/vendor/products',function (){
            return response()->json(['Welcome Vendor dashboard']);
        });
    });

    // Admin এবং Vendor উভয়ের জন্য
    Route::middleware('role:admin,vendor')->group(function () {
        Route::get('/reports', function (){
            return response()->json(['Welcome reports']);
        });    
    });

    // Customer এর জন্য
    Route::middleware('role:customer')->group(function () {
        Route::get('/my-orders', function (){
            return response()->json(['Customer orders']);
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
    Route::post('/payment/bkash/initiate', [PaymentController::class, 'initiateBkash']);

});

 
