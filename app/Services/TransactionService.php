<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    /**
     * Buat transaction baru untuk order
     * 
     * Selalu buat transaction baru dengan unique code karena:
     * - Midtrans tidak allow reuse order_id
     * - Snap token punya expiry time
     * - User bisa refresh page kapan saja
     */
    public function createTransaction(Order $order): Transaction
    {
        Log::info('Creating new transaction for order', ['order_id' => $order->id, 'order_code' => $order->code]);

        // Delete ALL pending transactions (mereka tidak akan pernah dipakai lagi)
        // Hanya settlement/paid transactions yang relevant
        $deleted = Transaction::where('order_id', $order->id)
            ->where('status', 'pending')
            ->delete();

        Log::info('Deleted old pending transactions', ['count' => $deleted]);

        // Generate unique transaction code dengan timestamp
        // Format: PAY-{ORDER_CODE}-{TIMESTAMP}-{RANDOM}
        // Timestamp memastikan unique order_id untuk setiap Midtrans API request
        $code = 'PAY-' . $order->code . '-' . time() . '-' . Str::random(4);

        Log::info('Generating new transaction code', ['code' => $code]);

        $transaction = Transaction::create([
            'code' => $code,
            'order_id' => $order->id,
            'user_id' => auth()->user()->id,
            'gateway_transaction_id' => null,
            'payment_method' => null,
            'payment_channel' => null,
            'amount' => $order->total_amount,
            'currency' => 'IDR',
            'status' => 'pending',
        ]);

        Log::info('New transaction created', [
            'transaction_id' => $transaction->id,
            'transaction_code' => $transaction->code,
        ]);

        return $transaction;
    }

    /**
     * Handle callback dari Midtrans
     */
    public function handleCallback(array $data): void
    {
        $transactionCode = $data['order_id'] ?? null;
        $transactionStatus = $data['transaction_status'] ?? null;
        $midtransTransactionId = $data['transaction_id'] ?? null;

        Log::info('Midtrans Callback received', [
            'transaction_code' => $transactionCode,
            'midtrans_transaction_id' => $midtransTransactionId,
            'transaction_status' => $transactionStatus,
        ]);

        if (!$transactionCode && !$midtransTransactionId) {
            Log::warning('Callback received without transaction code or transaction_id');
            return;
        }

        // Try to find transaction by order_id (transaction code) first
        $transaction = Transaction::where('code', $transactionCode)->first();

        // If not found by order_id, try by midtrans transaction_id
        if (!$transaction && $midtransTransactionId) {
            Log::info('Transaction not found by code, trying by gateway_transaction_id', [
                'gateway_transaction_id' => $midtransTransactionId,
            ]);
            $transaction = Transaction::where('gateway_transaction_id', $midtransTransactionId)->first();
        }

        if (!$transaction) {
            Log::warning('Transaction not found for callback', [
                'code' => $transactionCode,
                'gateway_transaction_id' => $midtransTransactionId,
            ]);
            return;
        }

        Log::info('Transaction found', [
            'transaction_id' => $transaction->id,
            'transaction_code' => $transaction->code,
        ]);

        // Map status lebih dulu sebelum update
        $mappedStatus = $this->mapTransactionStatus($transactionStatus);

        Log::info('Updating transaction with callback data', [
            'transaction_id' => $transaction->id,
            'old_status' => $transaction->status,
            'new_status' => $mappedStatus,
        ]);

        // Update transaction details
        $transaction->update([
            'gateway_transaction_id' => $midtransTransactionId,
            'payment_method' => $data['payment_type'] ?? null,
            'payment_channel' => $data['payment_channel'] ?? null,
            'signature' => $data['signature_key'] ?? null,
            'callback_response' => $data,
            'status' => $mappedStatus,
        ]);

        $order = $transaction->order;

        // Update order payment status
        if ($mappedStatus === 'success') {
            Log::info('Payment successful, updating order to PAID', [
                'order_id' => $order->id,
                'order_code' => $order->code,
            ]);
            
            $order->update([
                'payment_status' => 'PAID',
            ]);
        } elseif ($mappedStatus === 'failed' || $mappedStatus === 'expired' || $mappedStatus === 'refunded') {
            Log::info('Payment failed/cancelled, order remains UNPAID', [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'reason' => $mappedStatus,
            ]);
            
            $order->update([
                'payment_status' => 'UNPAID',
            ]);
        }
    }

    /**
     * Map status dari Midtrans ke aplikasi
     */
    private function mapTransactionStatus(string $midtransStatus): string
    {
        // Map ke enum values di transactions table:
        // 'pending','success','failed','expired','refunded'
        return match ($midtransStatus) {
            'capture', 'settlement' => 'success',
            'pending' => 'pending',
            'deny' => 'failed',
            'cancel' => 'failed',
            'expire' => 'expired',
            'refund', 'refund_pending' => 'refunded',
            default => 'pending',
        };
    }
}
