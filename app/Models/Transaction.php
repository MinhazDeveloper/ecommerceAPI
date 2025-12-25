<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'amount',
        'gateway',
        'transaction_id',
        'payment_id',
        'status',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array', // বিকাশের রেসপন্স অটোমেটিক অ্যারেতে রূপান্তর করবে
        'amount'   => 'float',
    ];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * রিলেশনশিপ: এই ট্রানজেকশনটি কোন অর্ডারের আন্ডারে।
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
