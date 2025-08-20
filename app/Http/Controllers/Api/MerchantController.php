<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Promotion;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MerchantController extends Controller
{
    // Daftar sebagai mitra
    public function registerAsMerchant(Request $request)
    {
        $request->validate([
            'business_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            // ... dokumen lainnya
        ]);

        $user = Auth::user();

        // Cek apakah user sudah terdaftar sebagai merchant atau sedang dalam proses
        if (Merchant::where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Anda sudah mengajukan pendaftaran merchant atau sudah terdaftar.'], 409);
        }

        // Buat entri merchant baru, status default is_approved = false
        $merchant = Merchant::create([
            'user_id' => $user->id,
            'business_name' => $request->business_name,
            'address' => $request->address,
            'is_approved' => false, // Menunggu persetujuan admin
        ]);

        // Beri peran 'merchant' pada user ini
        // Jika user sebelumnya 'user', sekarang punya peran 'user' dan 'merchant'
        $user->assignRole('merchant'); 

        return response()->json(['message' => 'Pendaftaran merchant berhasil, menunggu persetujuan admin.']);
    }

    // Membuat promosi baru
    public function createPromotion(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
        ]);

        $merchant = Auth::user()->merchant;

        // Pastikan user ini adalah merchant dan sudah disetujui
        if (!$merchant || !$merchant->is_approved) {
            return response()->json(['message' => 'Anda bukan merchant yang disetujui.'], 403);
        }

        $promotion = $merchant->promotions()->create([
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
            'is_approved' => false, // Perlu persetujuan admin
        ]);

        return response()->json(['message' => 'Promosi berhasil dibuat, menunggu persetujuan admin.', 'promotion' => $promotion]);
    }

    // Melihat promosi yang dibuat oleh merchant itu sendiri
    public function viewOwnPromotions()
    {
        $merchant = Auth::user()->merchant;
        if (!$merchant) {
            return response()->json(['message' => 'Anda bukan merchant.'], 403);
        }
        $promotions = $merchant->promotions()->get();
        return response()->json($promotions);
    }

    // Merchant memvalidasi dan meredeem kode voucher user
    public function redeemUserVoucher(Request $request)
    {
        $request->validate(['voucher_code' => 'required|string']);

        $voucher = Voucher::where('code', $request->voucher_code)->first();

        if (!$voucher) {
            return response()->json(['message' => 'Voucher tidak ditemukan.'], 404);
        }

        if ($voucher->is_redeemed) {
            return response()->json(['message' => 'Voucher sudah digunakan.'], 409);
        }

        // Pastikan voucher ini untuk promo yang ditawarkan oleh merchant yang sedang login
        if ($voucher->promotion->merchant_id !== Auth::user()->merchant->id) {
            return response()->json(['message' => 'Voucher ini bukan untuk merchant Anda.'], 403);
        }

        $voucher->is_redeemed = true;
        $voucher->redeemed_at = now();
        $voucher->save();

        // TODO: Logika untuk memberikan cashback ke user atau pembayaran ke merchant
        // Ini bisa melibatkan tabel Wallet atau transaksi keuangan.

        return response()->json(['message' => 'Voucher berhasil diredeem.']);
    }
}