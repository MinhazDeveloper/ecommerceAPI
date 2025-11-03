<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'cart_key',
        'total_price'
    ];
    /**
     * কার্ট আইটেমের সাথে সম্পর্ক (Relationship)
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        // নিশ্চিত করুন যে ডেটাবেসে total_price কলামটি decimal বা float ধরনের।
        'total_price' => 'decimal:2', 
    ];
}
