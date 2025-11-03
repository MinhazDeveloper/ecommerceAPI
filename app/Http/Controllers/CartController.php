<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product; // Assume Product model exists
use Illuminate\Support\Str;

class CartController extends Controller
{
    // Helper function to get the current or create a new Cart
    private function getOrCreateCart(Request $request): Cart
    {
        // 1. Authenticated User Cart check
        if ($request->user()) {
            return Cart::firstOrCreate(['user_id' => $request->user()->id]);
        } 
        
        // 2. Guest User Cart check (based on cart_key from header/input)
        $cart_key = $request->header('X-Cart-Key') ?? $request->input('cart_key'); 
        
        if ($cart_key) {
             $cart = Cart::whereNull('user_id')->where('cart_key', $cart_key)->first();
             if ($cart) {
                 return $cart;
             }
        }
        
        // 3. Create a new cart for guest, generate new unique key
        $new_cart_key = Str::random(32);
        return Cart::create([
            'cart_key' => $new_cart_key,
            'user_id' => null,
        ]);
    }
    // Helper function to update the cart's total price
    private function updateCartTotalPrice(Cart $cart): void
    {
        // Reload items to ensure latest data is used
        $cart->load('items'); 
        
        $totalPrice = $cart->items->sum(function ($item) {
            return $item->quantity * $item->price_at_addition;
        });
        
        $cart->total_price = $totalPrice;
        $cart->save();
    }
    // 1. Get User's Cart
    public function index(Request $request)
    {
        $cart = $this->getOrCreateCart($request); 
        $cart->load('items.product');

        return response()->json([
            'status' => 'success',
            'cart_id' => $cart->id,
            'cart_key' => $cart->cart_key,
            'total_price' => $cart->total_price,
            'cart_items' => $cart->items
        ]);
    }
    // 2. Add Product to Cart
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);
        $quantity = $request->quantity ?? 1;
        $cart = $this->getOrCreateCart($request); 

        $cartItem = CartItem::where('cart_id', $cart->id)
                            ->where('product_id', $product->id)
                            ->first();

        if ($cartItem) {
            $cartItem->quantity += $quantity;
            $cartItem->save();
            $message = 'Quantity updated in cart.';
        } else {
            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price_at_addition' => $product->price, // Use product's current price
            ]);
            $message = 'Product added to cart successfully.';
        }
        
        $this->updateCartTotalPrice($cart); 

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'cart_key' => $cart->cart_key,
            'cart_item' => $cartItem->load('product')
        ], $cartItem->wasRecentlyCreated ? 201 : 200);
    }
    // 3. Remove Product from Cart (using product_id)
    public function destroy(Request $request, $productId)
    {
        $cart = $this->getOrCreateCart($request); 
        
        $deleted = CartItem::where('cart_id', $cart->id)
                           ->where('product_id', $productId)
                           ->delete();

        if ($deleted) {
            $this->updateCartTotalPrice($cart); 
            
            return response()->json([
                'status' => 'success',
                'message' => 'Product removed from cart.'
            ]);
        }
        
        return response()->json([
            'status' => 'error',
            'message' => 'Product not found in cart.'
        ], 404);
    }
}
