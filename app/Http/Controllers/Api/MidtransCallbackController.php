<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\Notification;
use Illuminate\Http\Request;
use Midtrans\Notification as MidtransNotification;

class MidtransCallbackController extends Controller
{
    /**
     * Menangani callback dari Midtrans.
     */
    public function handle(Request $request)
    {
        try {
            $notif = new MidtransNotification();
            $status = $notif->transaction_status;
            $orderId = $notif->order_id;

            $vouchers = Voucher::where('transaction_id', $orderId)->get();
            if ($vouchers->isEmpty()) {
                return response()->json(['message' => 'Voucher tidak ditemukan.'], 404);
            }

            if ($status === 'settlement') {
                foreach ($vouchers as $voucher) {
                    $voucher->is_paid = true;
                    $voucher->save();

                    Notification::create([
                        'user_id' => $voucher->user_id,
                        'type' => 'payment_confirmed',
                        'message' => "Pembayaran untuk voucher {$voucher->code} berhasil.",
                    ]);
                }
            }

            return response()->json(['message' => 'Callback berhasil diproses.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }
}
