<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\Voucher;
use App\Models\Notification;
use App\Models\PointLog;
use App\Models\UserWallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Midtrans\Config;
use Midtrans\Snap;

class UserController extends Controller
{
    /**
     * Menampilkan daftar promosi dengan filter kategori.
     */
    public function viewPromotions(Request $request)
    {
        $query = Promotion::where('is_approved', true)
            ->with('merchant')
            ->select('id', 'merchant_id', 'title', 'description', 'price', 'original_price', 'terms_conditions', 'location', 'category', 'photo_path');

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $promotions = $query->get();
        return response()->json($promotions);
    }

    /**
     * Melakukan top-up AppWallet (dummy, langsung sukses).
     */
    public function topUpWallet(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|in:50000', // Hanya Rp 50.000 untuk dummy
        ]);

        $user = Auth::user();
        $wallet = UserWallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

        DB::beginTransaction();
        try {
            // Membuat record transaksi
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => $request->amount,
                'type' => 'topup',
                'status' => 'success',
            ]);

            // Menambah saldo
            $wallet->balance += $request->amount;
            $wallet->save();

            // Membuat notifikasi
            Notification::create([
                'user_id' => $user->id,
                'type' => 'topup_confirmed',
                'message' => "Top-up Rp {$request->amount} berhasil. Saldo AppWallet Anda sekarang Rp {$wallet->balance}.",
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Top-up Rp 50.000 berhasil.',
                'balance' => $wallet->balance,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Membeli voucher dengan metode AppWallet, Midtrans, atau WhatsApp.
     */
    public function purchasePromotion(Request $request)
    {
        $request->validate([
            'promotion_id' => 'required|exists:promotions,id',
            'quantity' => 'required|integer|min:1|max:10',
            'payment_method' => 'required|in:appwallet,midtrans,whatsapp',
        ]);

        $promotion = Promotion::find($request->promotion_id);

        if (!$promotion || !$promotion->is_approved) {
            return response()->json(['message' => 'Promosi tidak ditemukan atau belum aktif.'], 404);
        }

        if ($promotion->available_seats !== null && $promotion->available_seats < $request->quantity) {
            return response()->json(['message' => 'Jumlah slot tidak mencukupi.'], 400);
        }

        $user = Auth::user();
        $totalPrice = $promotion->price * $request->quantity; // Harga dalam Rupiah
        $pointsEarned = $request->quantity * 10; // 10 poin per voucher
        $vouchers = [];

        DB::beginTransaction();
        try {
            // Cek saldo untuk AppWallet
            if ($request->payment_method === 'appwallet') {
                $wallet = UserWallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);
                if ($wallet->balance < $totalPrice) {
                    return response()->json(['message' => 'Saldo AppWallet tidak mencukupi. Silakan top-up.'], 400);
                }

                // Memotong saldo
                $wallet->balance -= $totalPrice;
                $wallet->save();

                // Membuat record transaksi
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'amount' => $totalPrice,
                    'type' => 'purchase',
                    'status' => 'success',
                    'voucher_id' => null, // Akan diupdate per voucher
                ]);
            }

            // Mengurangi slot jika ada
            if ($promotion->available_seats !== null) {
                $promotion->available_seats -= $request->quantity;
                $promotion->save();
            }

            // Membuat voucher
            $orderId = 'ORDER-' . Str::random(10);
            for ($i = 0; $i < $request->quantity; $i++) {
                $voucherCode = Str::upper(Str::random(10));
                $qrPath = 'vouchers/' . $voucherCode . '.png';
                QrCode::format('png')->size(200)->generate($voucherCode, storage_path('app/public/' . $qrPath));

                $voucher = Voucher::create([
                    'user_id' => $user->id,
                    'promotion_id' => $promotion->id,
                    'code' => $voucherCode,
                    'payment_method' => $request->payment_method,
                    'qr_path' => $qrPath,
                    'status' => 'pending',
                    'is_redeemed' => false,
                    'is_paid' => $request->payment_method === 'appwallet' ? true : false,
                    'transaction_id' => $orderId,
                ]);

                // Update transaksi dengan voucher_id untuk AppWallet
                if ($request->payment_method === 'appwallet') {
                    WalletTransaction::where('user_id', $user->id)
                        ->where('type', 'purchase')
                        ->whereNull('voucher_id')
                        ->latest()
                        ->take(1)
                        ->update(['voucher_id' => $voucher->id]);
                }

                // Log poin
                PointLog::create([
                    'user_id' => $user->id,
                    'points' => 10,
                    'source' => 'purchase',
                    'voucher_id' => $voucher->id,
                ]);

                $vouchers[] = $voucher;
            }

            // Update poin dan membership
            $user->points += $pointsEarned;
            $previousLevel = $user->membership_level;
            $user->updateMembershipLevel();
            $user->save();

            // Notifikasi voucher
            Notification::create([
                'user_id' => $user->id,
                'type' => 'voucher_created',
                'message' => "Voucher untuk {$promotion->title} telah ditambahkan ke akun Anda. +{$pointsEarned} poin.",
            ]);

            // Notifikasi ke merchant
            Notification::create([
                'user_id' => $promotion->merchant->user_id,
                'type' => 'order_placed',
                'message' => "Ada {$request->quantity} pesanan baru untuk {$promotion->title} dari {$user->name}.",
            ]);

            // Notifikasi naik level
            if ($previousLevel !== $user->membership_level) {
                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'membership_upgraded',
                    'message' => "Selamat, Anda telah naik ke level {$user->membership_level} membership!",
                ]);
            }

            // Handle Midtrans
            if ($request->payment_method === 'midtrans') {
                Config::$serverKey = env('MIDTRANS_SERVER_KEY');
                Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
                Config::$isSanitized = true;
                Config::$is3ds = true;

                $params = [
                    'transaction_details' => [
                        'order_id' => $orderId,
                        'gross_amount' => $totalPrice,
                    ],
                    'customer_details' => [
                        'first_name' => $user->name,
                        'email' => $user->email,
                    ],
                ];

                $snapToken = Snap::getSnapToken($params);

                DB::commit();
                return response()->json([
                    'message' => 'Pembelian dimulai. Silakan lanjutkan pembayaran melalui Midtrans.',
                    'vouchers' => $vouchers,
                    'snap_token' => $snapToken,
                    'points_earned' => $pointsEarned,
                    'membership_level' => $user->membership_level,
                ]);
            }

            // Handle WhatsApp
            if ($request->payment_method === 'whatsapp') {
                Notification::create([
                    'user_id' => $promotion->merchant->user_id,
                    'type' => 'payment_pending',
                    'message' => "Pembayaran Rp {$totalPrice} untuk {$request->quantity} voucher {$promotion->title} melalui WhatsApp menunggu konfirmasi.",
                ]);

                DB::commit();
                return response()->json([
                    'message' => 'Pembelian melalui WhatsApp dimulai. Silakan kirim bukti transfer ke merchant.',
                    'vouchers' => $vouchers,
                    'points_earned' => $pointsEarned,
                    'membership_level' => $user->membership_level,
                ]);
            }

            // AppWallet
            DB::commit();
            return response()->json([
                'message' => 'Pembelian berhasil. Voucher telah ditambahkan ke My Vouchers.',
                'vouchers' => $vouchers,
                'points_earned' => $pointsEarned,
                'membership_level' => $user->membership_level,
                'balance' => $wallet->balance,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Booking jadwal untuk voucher.
     */
    public function bookSchedule(Request $request, Voucher $voucher)
    {
        $request->validate([
            'booking_date' => 'required|date|after:today',
            'booking_time' => 'required|date_format:H:i',
        ]);

        if ($voucher->user_id !== Auth::id()) {
            return response()->json(['message' => 'Anda tidak memiliki akses ke voucher ini.'], 403);
        }

        if ($voucher->status !== 'pending') {
            return response()->json(['message' => 'Voucher sudah dibooking atau digunakan.'], 400);
        }

        if (!$voucher->is_paid) {
            return response()->json(['message' => 'Voucher belum dibayar.'], 400);
        }

        $promotion = $voucher->promotion;

        if ($promotion->start_time && $promotion->end_time) {
            $bookingDateTime = \Carbon\Carbon::parse($request->booking_date . ' ' . $request->booking_time);
            if ($bookingDateTime->lt($promotion->start_time) || $bookingDateTime->gt($promotion->end_time)) {
                return response()->json(['message' => 'Jadwal tidak sesuai dengan periode promosi.'], 400);
            }
        }

        $voucher->booking_date = $request->booking_date;
        $voucher->booking_time = $request->booking_time;
        $voucher->status = 'booked';
        $voucher->save();

        $user = Auth::user();
        $pointsEarned = 5;
        $user->points += $pointsEarned;
        $previousLevel = $user->membership_level;
        $user->updateMembershipLevel();
        $user->save();

        PointLog::create([
            'user_id' => $user->id,
            'points' => $pointsEarned,
            'source' => 'booking',
            'voucher_id' => $voucher->id,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'type' => 'booking_confirmed',
            'message' => "Jadwal untuk voucher {$voucher->code} telah dikonfirmasi: {$request->booking_date} jam {$request->booking_time}. +{$pointsEarned} poin.",
        ]);

        if ($previousLevel !== $user->membership_level) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'membership_upgraded',
                'message' => "Selamat, Anda telah naik ke level {$user->membership_level} membership!",
            ]);
        }

        return response()->json([
            'message' => 'Booking berhasil.',
            'points_earned' => $pointsEarned,
            'membership_level' => $user->membership_level,
        ]);
    }

    /**
     * Menampilkan daftar voucher milik user.
     */
    public function viewMyVouchers()
    {
        $vouchers = Auth::user()->vouchers()->with('promotion.merchant')->get();
        return response()->json($vouchers);
    }

    /**
     * Menampilkan kode voucher dan QR code.
     */
    public function showVoucherCode(Voucher $voucher)
    {
        if ($voucher->user_id !== Auth::id()) {
            return response()->json(['message' => 'Anda tidak memiliki akses ke voucher ini.'], 403);
        }
        return response()->json([
            'voucher_code' => $voucher->code,
            'qr_url' => $voucher->qr_path ? url('storage/' . $voucher->qr_path) : null,
            'status' => $voucher->status,
            'is_redeemed' => $voucher->is_redeemed,
            'is_paid' => $voucher->is_paid,
            'booking_date' => $voucher->booking_date,
            'booking_time' => $voucher->booking_time,
        ]);
    }

    /**
     * Menampilkan poin dan riwayat poin.
     */
    public function viewPoints()
    {
        $user = Auth::user();
        $pointLogs = $user->pointLogs()->with('voucher.promotion')->latest()->get();
        return response()->json([
            'points' => $user->points,
            'membership_level' => $user->membership_level,
            'point_logs' => $pointLogs,
        ]);
    }

    /**
     * Menampilkan saldo AppWallet.
     */
    public function viewWallet()
    {
        $user = Auth::user();
        $wallet = UserWallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);
        return response()->json([
            'balance' => $wallet->balance,
        ]);
    }

    /**
     * Menampilkan riwayat transaksi AppWallet.
     */
    public function viewWalletTransactions()
    {
        $user = Auth::user();
        $transactions = $user->walletTransactions()->with('voucher.promotion')->latest()->get();
        return response()->json([
            'transactions' => $transactions,
        ]);
    }
}