<?php

namespace App\Services;

use Ihasan\Bkash\Bkash;

class BkashService {

    protected $bkash;

    public function __construct() {
        
        $this->bkash = app(Bkash::class);
    }
    public function createPayment($amount, $orderId) {
        
        $data = [
            'amount' => (float) $amount,
            // 'amount' => $amount,
            'orderID' => $orderId,
            'intent' => 'sale',
            'callbackURL' => route('bkash.callback')
        ];
        return $this->bkash->createPayment($data);
    }
    
    
    public function executePayment($paymentId) {
        return $this->bkash->executePayment($paymentId);
    }

    public function queryPayment($paymentId) {
        return $this->bkash->queryPayment($paymentId);
    }
}