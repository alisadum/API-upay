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
    // Lihat profil merchant sendiri
    public function profile()
    {
        $merchant = Auth::user()->loadMissing('merchant')->merchant;
        if (!$merchant) return response()->json(['message' => 'Belum terdaftar sebagai merchant.'], 404);
        return response()->json($merchant);
    }

    // Update profil merchant (brand, alamat, whatsapp, foto)
    public function updateProfile(Request $request)
    {
        $request->validate([
            'business_name' => 'sometimes|required|string|max:255',
            'address'       => 'sometimes|required|string|max:255',
            'whatsapp'      => 'sometimes|required|string|max:25',
            'foto'          => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $merchant = Auth::user()->merchant;
        if (!$merchant) return response()->json(['message' => 'Belum terdaftar sebagai merchant.'], 404);

        if ($request->has('business_name')) $merchant->business_name = $request->business_name;
        if ($request->has('address'))       $merchant->address       = $request->address;
        if ($request->has('whatsapp'))      $merchant->whatsapp      = $request->whatsapp;

        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('merchants', 'public');
            $merchant->photo_path = $path;
        }

        $merchant->save();

        return response()->json([
            'message'  => 'Profil merchant berhasil diperbarui.',
            'merchant' => $merchant
        ]);
    }

    // Bikin promosi (butuh merchant approved)
    public function createPromotion(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'price'       => 'required|numeric|min:0',
        ]);

        $merchant = Auth::user()->merchant;

        if (!$merchant || !$merchant->is_approved) {
            return response()->json(['message' => 'Anda bukan merchant yang disetujui.'], 403);
        }

        $promotion = $merchant->promotions()->create([
            'title'       => $request->title,
            'description' => $request->description,
            'price'       => $request->price,
            'is_approved' => false, // nunggu admin
        ]);

        return response()->json([
            'message'   => 'Promosi berhasil dibuat, menunggu persetujuan admin.',
            'promotion' => $promotion
        ], 201);
    }

    // List promosi sendiri
    public function getOwnPromotions()
    {
        $merchant = Auth::user()->merchant;
        if (!$merchant) return response()->json(['message' => 'Anda bukan merchant.'], 403);

        return response()->json($merchant->promotions()->latest()->get());
    }

    // Redeem voucher
    public function redeemVoucher(Request $request)
    {
        $request->validate(['voucher_code' => 'required|string']);

        $merchant = Auth::user()->merchant;
        if (!$merchant) return response()->json(['message' => 'Anda bukan merchant.'], 403);

        $voucher = Voucher::with('promotion')->where('code', $request->voucher_code)->first();
        if (!$voucher) return response()->json(['message' => 'Voucher tidak ditemukan.'], 404);
        if ($voucher->is_redeemed) return response()->json(['message' => 'Voucher sudah digunakan.'], 409);
        if ($voucher->promotion->merchant_id !== $merchant->id) {
            return response()->json(['message' => 'Voucher ini bukan untuk merchant Anda.'], 403);
        }

        $voucher->is_redeemed = true;
        $voucher->redeemed_at = now();
        $voucher->save();

        return response()->json(['message' => 'Voucher berhasil diredeem.']);
    }
}
