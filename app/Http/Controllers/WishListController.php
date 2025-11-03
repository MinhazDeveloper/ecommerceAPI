<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wishlist;
use App\Models\Product;

class WishListController extends Controller
{
    // 1. Get User's Wishlist
    public function index(Request $request)
    {
        // Request user()->wishlist() depends on the relationship defined in User model: 
        // public function wishlist() { return $this->hasMany(Wishlist::class); }
        $wishlistItems = $request->user()->wishlist()->with('product')->get();
        
        return response()->json([
            'status' => 'success',
            'wishlist_items' => $wishlistItems
        ]);
    }
    // 2. Add Product to Wishlist
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $user_id = $request->user()->id;
        $product_id = $request->product_id;

        // Check if product is already in wishlist
        $wishlistItem = Wishlist::where('user_id', $user_id)
                                ->where('product_id', $product_id)
                                ->first();

        if ($wishlistItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product is already in your wishlist.'
            ], 409); // Conflict
        }

        $wishlistItem = Wishlist::create([
            'user_id' => $user_id,
            'product_id' => $product_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Product added to wishlist successfully.',
            'wishlist_item' => $wishlistItem->load('product')
        ], 201);
    }
    // 3. Remove Product from Wishlist (using product_id)
    public function destroy(Request $request, $productId)
    {
        $deleted = Wishlist::where('user_id', $request->user()->id)
                          ->where('product_id', $productId)
                          ->delete();

        if ($deleted) {
            return response()->json([
                'status' => 'success',
                'message' => 'Product removed from wishlist.'
            ]);
        }
        
        return response()->json([
            'status' => 'error',
            'message' => 'Product not found in wishlist.'
        ], 404);
    }
}
