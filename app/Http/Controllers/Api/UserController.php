<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserController extends Controller
{
    // Eksplorasi promo yang sudah disetujui
    public function viewPromotions()
    {
        // Hanya tampilkan promosi yang sudah disetujui admin
        $promotions = Promotion::where('is_approved', true)->with('merchant')->get();
        return response()->json($promotions);
    }

    // Pembelian deal / promosi
    public function purchasePromotion(Request $request)
    {
        // Membutuhkan validasi purchase_id dan payment_method
        $request->validate([
            'promotion_id' => 'required|exists:promotions,id',
            // 'payment_method' => 'required|string', // Contoh, bisa diperluas
        ]);

        $promotion = Promotion::find($request->promotion_id);

        if (!$promotion || !$promotion->is_approved) {
            return response()->json(['message' => 'Promosi tidak ditemukan atau belum aktif.'], 404);
        }

        $user = Auth::user();

        // Logika pembayaran dummy (asumsi selalu berhasil)
        // Di sini nanti terintegrasi dengan payment gateway

        // Buat voucher baru untuk user
        $voucher = Voucher::create([
            'user_id' => $user->id,
            'promotion_id' => $promotion->id,
            'code' => Str::upper(Str::random(10)), // Kode voucher unik
            'is_redeemed' => false,
        ]);

        return response()->json(['message' => 'Pembelian berhasil! Voucher Anda: ' . $voucher->code, 'voucher' => $voucher]);
    }

    // Melihat daftar e-voucher milik user
    public function viewMyVouchers()
    {
        $vouchers = Auth::user()->vouchers()->with('promotion.merchant')->get();
        return response()->json($vouchers);
    }

    // User bisa melihat kode voucher untuk di-redeem di merchant
    // Redeem fisik akan dilakukan oleh merchant
    public function showVoucherCode(Voucher $voucher)
    {
        if ($voucher->user_id !== Auth::id()) {
            return response()->json(['message' => 'Anda tidak memiliki akses ke voucher ini.'], 403);
        }
        return response()->json(['voucher_code' => $voucher->code, 'is_redeemed' => $voucher->is_redeemed]);
    }
}