<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sub_total',
        'shipping_fee',
        'discount',
        'total_amount',
        'status',
        'is_paid',
    ];
    protected $casts = [
        'is_paid' => 'boolean',
        'total_amount' => 'float',
    ];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

}
