<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\PaymentController;

// -----------------------
// Public routes (no auth)
// -----------------------
Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    //products
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    
    //categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);

    // -----------------------
    // Protected routes (auth:sanctum required)
    // -----------------------
    Route::middleware('auth:sanctum')->group(function () {
        //for admin and vendor
        Route::middleware('role:admin,vendor')->group(function () {
            Route::post('/products', [ProductController::class, 'store']);
            Route::put('/products/{id}', [ProductController::class, 'update']);
            Route::delete('/products/{id}', [ProductController::class, 'destroy']);

            Route::post('/categories', [CategoryController::class, 'store']);
            Route::put('/categories/{id}', [CategoryController::class, 'update']);
            Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
        });   
        // Customer এর জন্য
        Route::middleware('role:customer')->group(function () {
            Route::get('/my-orders', function (){
                return response()->json(['Customer orders']);
            });
            //payment integration(bkash checkout)
            Route::post('/checkout/bkash', [PaymentController::class, 'checkout']);   
        });

        // Current authenticated user info
        Route::get('/user', function (Request $request) {
            return response()->json($request->user());
        });

        //Add to cart
        Route::prefix('cart')->group(function () {
            Route::get('/', [CartController::class, 'index']); 
            Route::post('/', [CartController::class, 'store']); 
            Route::delete('/{productId}', [CartController::class, 'destroy']); 
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
        
        
        //add cart & wishlist
        
        //WishList route
        Route::prefix('wishlist')->group(function () {
            Route::get('/', [WishlistController::class, 'index']); 
            Route::post('/', [WishlistController::class, 'store']); 
            // DELETE: /api/wishlist/{productId} (Remove from Wishlist)
            Route::delete('/{productId}', [WishlistController::class, 'destroy']); 
        });

    });
});    



// bkash test api route
Route::get('/debug-bkash', function () {
    $url = env('SANDBOX_BASE_URL') . '/tokenized/checkout/token/grant';
    // $url = "https://checkout.sandbox.bka.sh/v1.2.0-beta/checkout/payment/create";
    
    try {
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'username' => env('BKASH_USERNAME'),
            'password' => env('BKASH_PASSWORD'),
            'Accept' => 'application/json',   
            'Content-Type' => 'application/json'
        ])->post($url, [
            'app_key' => env('BKASH_APP_KEY'),
            'app_secret' => env('BKASH_APP_SECRET'),
        ]);

        return response()->json([
            'status' => 'Testing bKash Connection',
            'requested_url' => $url,
            'env_data_found' => [
                'username' => env('BKASH_USERNAME') ? 'Yes' : 'No',
                'app_key' => env('BKASH_APP_KEY') ? 'Yes' : 'No',
            ],
            'bkash_raw_response' => $response->json(),
            'http_status' => $response->status(),
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});
