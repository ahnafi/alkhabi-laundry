<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;

class PaymentController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');

        $this->transactionService = $transactionService;
    }

    public function invoice(Order $order)
    {

        Log::info('Create invoice with data' . $order->code);

        // Memastikan order milik user yang login
        if ($order->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Create transaction and get snap token
        $transaction = $this->transactionService->createTransaction($order);
        
        $itemDetails = $this->getItemDetails($order);
        $grossAmount = intval($order->total_amount);
        
        // Calculate total from items to verify
        $itemsTotal = 0;
        foreach ($itemDetails as $item) {
            $itemsTotal += (int)$item['price'] * (int)$item['quantity'];
        }
        
        // Log untuk debugging
        Log::info('Midtrans Request Debug', [
            'order_code' => $order->code,
            'gross_amount' => $grossAmount,
            'items_total' => $itemsTotal,
            'items' => $itemDetails,
        ]);
        
        // Jika tidak sesuai, gunakan gross_amount langsung tanpa item_details
        if ($itemsTotal !== $grossAmount) {
            Log::warning("Item total ($itemsTotal) not equal to gross_amount ($grossAmount), using gross_amount only");
            $params = [
                'transaction_details' => [
                    'order_id' => $transaction->code,
                    'gross_amount' => $grossAmount,
                ],
                'customer_details' => [
                    'first_name' => $order->customer->name,
                    'email' => $order->customer->email,
                    'phone' => $order->customer->phone ?? '08000000000',
                ],
            ];
        } else {
            $params = [
                'transaction_details' => [
                    'order_id' => $transaction->code,
                    'gross_amount' => $grossAmount,
                ],
                'customer_details' => [
                    'first_name' => $order->customer->name,
                    'email' => $order->customer->email,
                    'phone' => $order->customer->phone ?? '08000000000',
                ],
                'item_details' => $itemDetails,
            ];
        }

        try {
            $snapToken = Snap::getSnapToken($params);
            
            // Update transaction dengan snap token
            $transaction->update([
                'payment_token' => $snapToken,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            abort(500, 'Gagal membuat snap token: ' . $e->getMessage());
        }

        $clientKey = config('midtrans.client_key');

        Log::info('transaction created' . $transaction->code);

        return view('invoice.index', compact('order', 'transaction', 'snapToken', 'clientKey'));
    }

    /**
     * Handle payment success dari JavaScript frontend
     * Dipanggil ketika user berhasil bayar di Midtrans popup
     */
    public function handleSuccess(Request $request)
    {
        $orderId = $request->input('order_id');
        $transactionId = $request->input('transaction_id');
        $status = $request->input('status');

        Log::info('Payment success from frontend', [
            'order_id' => $orderId,
            'midtrans_transaction_id' => $transactionId,
            'status' => $status,
        ]);

        $order = Order::find($orderId);

        if (!$order) {
            Log::warning('Order not found in handleSuccess', ['order_id' => $orderId]);
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Verify user authorization
        if ($order->user_id !== auth()->id()) {
            Log::warning('Unauthorized payment success attempt', [
                'order_id' => $orderId,
                'user_id' => auth()->id(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Update order status to PAID
        if ($status === 'settlement' || $status === 'capture') {
            Log::info('Updating order to PAID', ['order_id' => $orderId]);
            
            $order->update([
                'payment_status' => 'PAID',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order status updated to PAID',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Payment status is not settlement/capture',
        ]);
    }

    /**
    public function handleCallback(Request $request)
    {
        Log::info('Callback received from Midtrans', [
            'all_data' => $request->all(),
        ]);

        $this->transactionService->handleCallback($request->all());

        Log::info('Callback processing completed');

        return response()->json(['status' => 'ok']);
    }

    /**
     * Format item details untuk Midtrans
     */
    private function getItemDetails(Order $order): array
    {
        $items = [];

        // Add order items
        foreach ($order->orderItems as $item) {
            $items[] = [
                'id' => 'SERVICE-' . $item->service_id,
                'price' => (int)round($item->service->price_per_unit ?? 0),
                'quantity' => (int)$item->qty,
                'name' => substr($item->service->name ?? 'Service', 0, 50),
            ];
        }

        // Add delivery fee as item
        if ($order->delivery_fee > 0) {
            $items[] = [
                'id' => 'DELIVERY',
                'price' => (int)round($order->delivery_fee),
                'quantity' => 1,
                'name' => 'Biaya Pengiriman',
            ];
        }

        return $items;
    }
}
