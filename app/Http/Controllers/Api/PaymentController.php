<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;

class PaymentController extends Controller
{
    public function __construct()
    {
        // Set konfigurasi Midtrans saat controller diinisialisasi
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    /**
     * Membuat transaksi dan mendapatkan Snap Token.
     */
    public function createTransaction(Request $request)
    {
        // Validasi request (contoh sederhana)
        $request->validate([
            'order_id' => 'required|string|unique:orders,id', // Pastikan order_id unik
            'amount' => 'required|numeric|min:1000',
        ]);

        $user = $request->user();

        $params = [
            'transaction_details' => [
                'order_id' => $request->order_id,
                'gross_amount' => $request->amount,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            return response()->json(['snap_token' => $snapToken]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Menangani notifikasi dari Midtrans (webhook).
     */
    public function notificationHandler(Request $request)
    {
        try {
            $notification = new Notification();

            $transactionStatus = $notification->transaction_status;
            $orderId = $notification->order_id;
            $fraudStatus = $notification->fraud_status;

            // Logika untuk memproses status transaksi
            // Contoh: Cari order berdasarkan $orderId di database Anda
            // $order = Order::find($orderId);

            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'accept') {
                    // TODO: Update status order menjadi 'paid' atau 'processing'
                    // $order->update(['payment_status' => 'paid']);
                }
            } else if ($transactionStatus == 'settlement') {
                // TODO: Update status order menjadi 'paid' atau 'completed'
                // $order->update(['payment_status' => 'paid']);
            } else if ($transactionStatus == 'pending') {
                // TODO: Update status order menjadi 'pending'
                // $order->update(['payment_status' => 'pending']);
            } else if ($transactionStatus == 'deny') {
                // TODO: Update status order menjadi 'denied'
                // $order->update(['payment_status' => 'denied']);
            } else if ($transactionStatus == 'expire') {
                // TODO: Update status order menjadi 'expired'
                // $order->update(['payment_status' => 'expired']);
            } else if ($transactionStatus == 'cancel') {
                // TODO: Update status order menjadi 'cancelled'
                // $order->update(['payment_status' => 'cancelled']);
            }

            // Beri respons OK ke Midtrans agar tidak mengirim notifikasi berulang
            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            // Tangani jika ada error saat memproses notifikasi
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

