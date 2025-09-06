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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class UserController extends Controller
{
  
    public function createWallet(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:25|unique:user_wallets,phone_number',
            'pin' => 'required|string|min:6|max:6|confirmed',
        ]);

        $user = Auth::user();
        $existingWallet = UserWallet::where('user_id', $user->id)->first();
        if ($existingWallet) {
            return response()->json(['message' => 'App Wallet sudah terdaftar.'], 409);
        }

        $wallet = UserWallet::create([
            'user_id' => $user->id,
            'balance' => 0,
            'phone_number' => $request->phone_number,
            'pin' => Hash::make($request->pin),
        ]);

        return response()->json([
            'message' => 'App Wallet berhasil dibuat.',
            'data' => [
                'wallet' => [
                    'user_id' => $wallet->user_id,
                    'phone_number' => $wallet->phone_number,
                    'balance' => $wallet->balance,
                ],
            ],
        ], 201);
    }

    public function topUpWallet(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000',
            'pin' => 'required|string|min:6|max:6',
        ]);

        $user = Auth::user();
        $wallet = UserWallet::where('user_id', $user->id)->first();
        if (!$wallet) {
            return response()->json(['message' => 'App Wallet belum terdaftar.'], 404);
        }

        if (!Hash::check($request->pin, $wallet->pin)) {
            return response()->json(['message' => 'PIN salah.'], 401);
        }

        DB::beginTransaction();
        try {
            $wallet->balance += $request->amount;
            $wallet->save();

            WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => $request->amount,
                'type' => 'topup',
                'status' => 'success',
                'voucher_id' => null,
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Top-up wallet berhasil.',
                'data' => [
                    'balance' => $wallet->balance,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saat top-up wallet: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function purchasePromotion(Request $request)
    {
        $request->validate([
            'promotion_id' => 'required|exists:promotions,id',
            'quantity' => 'required|integer|min:1|max:10',
            'payment_method' => 'required|in:appwallet,midtrans,whatsapp',
            'pin' => 'required_if:payment_method,appwallet|string|min:6|max:6',
        ]);

        $promotion = Promotion::with('outlets')->find($request->promotion_id);
        if (!$promotion || !$promotion->is_approved) {
            return response()->json(['message' => 'Promosi tidak ditemukan atau belum aktif.'], 404);
        }

        if ($promotion->available_seats !== null && $promotion->available_seats < $request->quantity) {
            return response()->json(['message' => 'Jumlah slot tidak mencukupi.'], 400);
        }

        $user = Auth::user();
        $totalPrice = $promotion->price * $request->quantity;
        $pointsEarned = $request->quantity * 10;
        $vouchers = [];

        $voucherPath = storage_path('app/public/vouchers');
        if (!File::exists($voucherPath)) {
            File::makeDirectory($voucherPath, 0755, true);
        }
        if (!is_writable($voucherPath)) {
            Log::error('Folder vouchers tidak writable: ' . $voucherPath);
            return response()->json(['message' => 'Folder vouchers tidak dapat ditulis.'], 500);
        }

        DB::beginTransaction();
        try {
            if ($request->payment_method === 'appwallet') {
                $wallet = UserWallet::where('user_id', $user->id)->first();
                if (!$wallet) {
                    return response()->json(['message' => 'App Wallet belum terdaftar.'], 404);
                }
                if (!Hash::check($request->pin, $wallet->pin)) {
                    return response()->json(['message' => 'PIN salah.'], 401);
                }
                if ($wallet->balance < $totalPrice) {
                    return response()->json(['message' => 'Saldo AppWallet tidak mencukupi. Silakan top-up.'], 400);
                }
                $wallet->balance -= $totalPrice;
                $wallet->save();

                WalletTransaction::create([
                    'user_id' => $user->id,
                    'amount' => $totalPrice,
                    'type' => 'purchase',
                    'status' => 'success',
                    'voucher_id' => null,
                ]);
            }

            if ($promotion->available_seats !== null) {
                $promotion->available_seats -= $request->quantity;
                $promotion->save();
            }

            $orderId = 'ORDER-' . Str::uuid()->toString();
            for ($i = 0; $i < $request->quantity; $i++) {
                $voucherCode = Str::upper(Str::random(10));
                $qrPath = 'vouchers/' . $orderId . '-' . $i . '-' . time() . '.png';
                $fullPath = storage_path('app/public/' . $qrPath);

                try {
                    $qrCode = new QrCode($voucherCode);
                    $qrCode->setSize(200);
                    $writer = new PngWriter();
                    $result = $writer->write($qrCode);
                    file_put_contents($fullPath, $result->getString());

                    if (!File::exists($fullPath)) {
                        throw new \Exception('Gagal membuat file QR code untuk: ' . $voucherCode);
                    }
                } catch (\Exception $e) {
                    Log::error('Gagal membuat QR code: ' . $e->getMessage());
                    throw $e;
                }

                $voucher = Voucher::create([
                    'user_id' => $user->id,
                    'promotion_id' => $promotion->id,
                    'code' => $voucherCode,
                    'payment_method' => $request->payment_method,
                    'qr_path' => $qrPath,
                    'status' => $request->payment_method === 'appwallet' ? 'active' : 'pending',
                    'is_redeemed' => false,
                    'is_paid' => $request->payment_method === 'appwallet',
                    'transaction_id' => $orderId,
                    'start_at' => $promotion->start_time ?? now(),
                    'expire_at' => ($promotion->start_time ? (new \DateTime($promotion->start_time))->modify('+30 days')->format('Y-m-d H:i:s') : now()->addDays(30)),
                ]);

                if ($request->payment_method === 'appwallet') {
                    WalletTransaction::where('user_id', $user->id)
                        ->where('type', 'purchase')
                        ->whereNull('voucher_id')
                        ->latest()
                        ->take(1)
                        ->update(['voucher_id' => $voucher->id]);
                }

                $vouchers[] = $voucher;
            }

            if ($request->payment_method === 'appwallet') {
                $user->points += $pointsEarned;
                $previousLevel = $user->membership_level;
                // Ganti updateMembershipLevel dengan logika sederhana atau hapus jika tidak ada
                // Contoh logika sederhana:
                if ($user->points >= 100) {
                    $user->membership_level = 'gold';
                } elseif ($user->points >= 50) {
                    $user->membership_level = 'silver';
                } else {
                    $user->membership_level = 'bronze';
                }
                $user->save();

                PointLog::create([
                    'user_id' => $user->id,
                    'points' => 10,
                    'source' => 'purchase',
                    'voucher_id' => $vouchers[0]->id,
                ]);
            }

            $locationInfo = $promotion->outlets->isNotEmpty() ? $promotion->outlets->first()->address : ($promotion->location ?? 'Lokasi tidak tersedia');
            Notification::create([
                'user_id' => $user->id,
                'type' => 'voucher_created',
                'message' => "Voucher untuk {$promotion->title} di {$locationInfo} telah ditambahkan. " . ($request->payment_method === 'appwallet' ? "+{$pointsEarned} poin." : 'Tunggu konfirmasi pembayaran.'),
            ]);

            Notification::create([
                'user_id' => $promotion->merchant->user_id,
                'type' => 'order_placed',
                'message' => "Ada {$request->quantity} pesanan baru untuk {$promotion->title} di {$locationInfo} dari {$user->name}.",
            ]);

            if ($request->payment_method === 'appwallet' && $previousLevel !== $user->membership_level) {
                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'membership_upgraded',
                    'message' => "Selamat, Anda naik ke level {$user->membership_level} membership!",
                ]);
            }

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
                    'callbacks' => [
                        'finish' => route('payment.midtrans.callback'),
                    ],
                ];

                try {
                    $snapToken = Snap::getSnapToken($params);
                } catch (\Exception $e) {
                    Log::error('Gagal mendapatkan snap token Midtrans: ' . $e->getMessage());
                    throw $e;
                }

                DB::commit();
                return response()->json([
                    'message' => 'Pembelian dimulai. Lanjutkan pembayaran melalui Midtrans.',
                    'data' => [
                        'vouchers' => $vouchers,
                        'snap_token' => $snapToken,
                    ],
                ]);
            }

            if ($request->payment_method === 'whatsapp') {
                Notification::create([
                    'user_id' => $promotion->merchant->user_id,
                    'type' => 'payment_pending',
                    'message' => "Pembayaran Rp {$totalPrice} untuk {$request->quantity} voucher {$promotion->title} di {$locationInfo} menunggu konfirmasi via WhatsApp.",
                ]);

                DB::commit();
                return response()->json([
                    'message' => 'Pembelian via WhatsApp dimulai. Kirim bukti transfer ke merchant.',
                    'data' => [
                        'vouchers' => $vouchers,
                    ],
                ]);
            }

            DB::commit();
            return response()->json([
                'message' => 'Pembelian berhasil. Voucher telah ditambahkan ke My Vouchers.',
                'data' => [
                    'vouchers' => $vouchers,
                    'points_earned' => $pointsEarned,
                    'membership_level' => $user->membership_level,
                    'balance' => $wallet->balance ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saat membuat voucher: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function midtransCallback(Request $request)
    {
        $serverKey = env('MIDTRANS_SERVER_KEY');
        $orderId = $request->order_id;
        $statusCode = $request->status_code;
        $grossAmount = $request->gross_amount;

        $hashed = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        if ($hashed !== $request->signature_key) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $vouchers = Voucher::where('transaction_id', $orderId)->get();
        if ($vouchers->isEmpty()) {
            Log::error('No vouchers found for transaction_id: ' . $orderId);
            return response()->json(['message' => 'Voucher tidak ditemukan'], 404);
        }

        DB::beginTransaction();
        try {
            foreach ($vouchers as $voucher) {
                if ($statusCode == 200 && !$voucher->is_paid) {
                    $user = $voucher->user;
                    $promotion = $voucher->promotion;
                    $pointsEarned = 10;

                    $voucher->is_paid = true;
                    $voucher->status = 'active';
                    $voucher->save();

                    $user->points += $pointsEarned;
                    $previousLevel = $user->membership_level;
                    // Ganti updateMembershipLevel dengan logika sederhana
                    if ($user->points >= 100) {
                        $user->membership_level = 'gold';
                    } elseif ($user->points >= 50) {
                        $user->membership_level = 'silver';
                    } else {
                        $user->membership_level = 'bronze';
                    }
                    $user->save();

                    PointLog::create([
                        'user_id' => $user->id,
                        'points' => $pointsEarned,
                        'source' => 'purchase',
                        'voucher_id' => $voucher->id,
                    ]);

                    Notification::create([
                        'user_id' => $user->id,
                        'type' => 'payment_confirmed',
                        'message' => "Pembayaran voucher {$voucher->code} telah dikonfirmasi.",
                    ]);

                    if ($previousLevel !== $user->membership_level) {
                        Notification::create([
                            'user_id' => $user->id,
                            'type' => 'membership_upgraded',
                            'message' => "Selamat, Anda naik ke level {$user->membership_level} membership!",
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['message' => 'Pembayaran dikonfirmasi.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error di midtransCallback: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function viewMyVouchers()
    {
        $user = Auth::user();
        $vouchers = Voucher::where('user_id', $user->id)->with('promotion')->get();

        return response()->json([
            'message' => 'Voucher berhasil diambil.',
            'data' => $vouchers,
        ]);
    }

    public function showVoucherCode($voucher)
    {
        $user = Auth::user();
        $voucher = Voucher::where('user_id', $user->id)->findOrFail($voucher);

        return response()->json([
            'message' => 'Kode voucher berhasil diambil.',
            'data' => $voucher,
        ]);
    }

    public function bookSchedule(Request $request, $voucher)
    {
        return response()->json(['message' => 'Fitur booking belum diimplementasikan.'], 501);
    }

    public function viewPoints()
    {
        $user = Auth::user();

        return response()->json([
            'message' => 'Poin berhasil diambil.',
            'data' => [
                'points' => $user->points,
                'membership_level' => $user->membership_level,
            ],
        ]);
    }

    public function viewWallet()
    {
        $user = Auth::user();
        $wallet = UserWallet::where('user_id', $user->id)->firstOrFail();

        return response()->json([
            'message' => 'Saldo wallet berhasil diambil.',
            'data' => [
                'balance' => $wallet->balance,
                'phone_number' => $wallet->phone_number,
            ],
        ]);
    }

    public function viewWalletTransactions()
    {
        $user = Auth::user();
        $transactions = WalletTransaction::where('user_id', $user->id)->get();

        return response()->json([
            'message' => 'Transaksi wallet berhasil diambil.',
            'data' => $transactions,
        ]);
    }

    public function viewPromotions()
    {
        $promotions = Promotion::where('is_approved', true)->with('merchant')->get();

        return response()->json([
            'message' => 'Promosi berhasil diambil.',
            'data' => $promotions,
        ]);
    }
}