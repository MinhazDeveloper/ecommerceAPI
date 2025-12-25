<?php

namespace App\Http\Controllers;

use App\Services\BkashService;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $bkashService;

    public function __construct(BkashService $bkashService)
    {
        $this->bkashService = $bkashService;
    }
    public function checkout(Request $request) {
        $userId = auth()->id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            return DB::transaction(function () use ($request, $userId) {
                $order = Order::create([
                    'user_id'      => $userId,
                    'total_amount' => $request->amount,
                    'status'       => 'PENDING',
                    'order_number' => 'ORD-' . strtoupper(uniqid()), 
                ]);
                $transaction = Transaction::create([
                    'user_id'  => $userId,
                    'order_id' => $order->id,
                    'amount'   => $order->total_amount,
                    'status'   => 'PENDING',
                    'gateway'  => 'bkash', 
                ]);

                // ৩. বিকাশ পেমেন্ট ক্রিয়েট
                $response = $this->bkashService->createPayment($transaction->amount, $order->id);

                // বিকাশ রেসপন্স চেক
                if (!isset($response['bkashURL'])) {
                    \Log::error('bKash Response Error: ', $response);
                    return response()->json([
                        'message' => 'bKash initialization failed',
                        'details' => $response
                    ], 422);
                }

                // ৪. ট্রানজ্যাকশন আপডেট
                $transaction->update([
                    'payment_id' => $response['paymentID'],
                    'payload'    => $response 
                ]);

                return response()->json(['redirect_url' => $response['bkashURL']]);
            });
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], 500);
        }
    }
    public function callback(Request $request) {
        $transaction = Transaction::where('payment_id', $request->paymentID)->firstOrFail();

        if ($request->status !== 'success') {
            $transaction->update(['status' => 'FAILED']);
            return redirect(config('services.bkash.frontend_failed_url'));
        }

        try {
            $execute = $this->bkashService->executePayment($request->paymentID);
            
            if (isset($execute['transactionStatus']) && $execute['transactionStatus'] === 'Completed') {
                
                $transaction->update([
                    'trx_id' => $execute['trxID'],
                    'status' => 'SUCCESS',
                    'response' => json_encode($execute)
                ]);

                $transaction->order->update(['status' => 'PAID']);

                return redirect(config('services.bkash.frontend_success_url'));
            }
        } catch (\Exception $e) {
            \Log::error("bKash Error: " . $e->getMessage());
        }

        $transaction->update(['status' => 'FAILED']);
        return redirect(config('services.bkash.frontend_failed_url'));
    }

}
