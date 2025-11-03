<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'price_at_addition',
    ];
    /**
     * কার্ট মডেলে BelongsTo রিলেশনশিপ
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Product মডেলে BelongsTo রিলেশনশিপ
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    
    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price_at_addition' => 'decimal:2', 
    ];
}
