<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Promotion;
use App\Models\Voucher;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MerchantController extends Controller
{
    /**
     * Menampilkan profil merchant.
     */
    public function profile()
    {
        $merchant = Auth::user()->loadMissing('merchant')->merchant;
        if (!$merchant) {
            return response()->json(['message' => 'Anda belum terdaftar sebagai merchant.'], 404);
        }
        return response()->json($merchant);
    }

    /**
     * Memperbarui profil merchant.
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'business_name' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|required|string|max:255',
            'whatsapp' => 'sometimes|required|string|max:25',
            'foto' => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $merchant = Auth::user()->merchant;
        if (!$merchant) {
            return response()->json(['message' => 'Anda belum terdaftar sebagai merchant.'], 404);
        }

        if ($request->has('business_name')) {
            $merchant->business_name = $request->business_name;
        }
        if ($request->has('address')) {
            $merchant->address = $request->address;
        }
        if ($request->has('whatsapp')) {
            $merchant->whatsapp = $request->whatsapp;
        }

        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('merchants', 'public');
            $merchant->photo_path = $path;
        }

        $merchant->save();

        return response()->json([
            'message' => 'Profil merchant berhasil diperbarui.',
            'merchant' => $merchant
        ]);
    }

    /**
     * Membuat promosi baru.
     */
    public function createPromotion(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'original_price' => 'required|numeric|min:0',
            'terms_conditions' => 'required|string',
            'location' => 'required|string',
            'category' => 'required|string|in:kecantikan,makanan,travel',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'available_seats' => 'nullable|integer|min:0',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $merchant = Auth::user()->merchant;

        if (!$merchant || !$merchant->is_approved) {
            return response()->json(['message' => 'Anda bukan merchant yang disetujui.'], 403);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('promotions', 'public');
        }

        $promotion = $merchant->promotions()->create([
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
            'original_price' => $request->original_price,
            'terms_conditions' => $request->terms_conditions,
            'location' => $request->location,
            'category' => $request->category,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'available_seats' => $request->available_seats,
            'photo_path' => $photoPath,
            'is_approved' => false,
        ]);

        Notification::create([
            'user_id' => 1, // Asumsi admin ID 1
            'type' => 'new_promo',
            'message' => "Promosi baru '{$promotion->title}' dari {$merchant->business_name} menunggu persetujuan.",
        ]);

        return response()->json([
            'message' => 'Promosi berhasil dibuat, menunggu persetujuan admin.',
            'promotion' => $promotion
        ], 201);
    }

    /**
     * Memperbarui promosi.
     */
    public function updatePromotion(Request $request, Promotion $promotion)
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric|min:0',
            'original_price' => 'sometimes|required|numeric|min:0',
            'terms_conditions' => 'sometimes|required|string',
            'location' => 'sometimes|required|string',
            'category' => 'sometimes|required|string|in:kecantikan,makanan,travel',
            'start_time' => 'sometimes|nullable|date',
            'end_time' => 'sometimes|nullable|date|after:start_time',
            'available_seats' => 'sometimes|nullable|integer|min:0',
            'photo' => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $merchant = Auth::user()->merchant;
        if (!$merchant || $promotion->merchant_id !== $merchant->id) {
            return response()->json(['message' => 'Anda tidak memiliki akses ke promosi ini.'], 403);
        }

        if ($request->has('title')) {
            $promotion->title = $request->title;
        }
        if ($request->has('description')) {
            $promotion->description = $request->description;
        }
        if ($request->has('price')) {
            $promotion->price = $request->price;
        }
        if ($request->has('original_price')) {
            $promotion->original_price = $request->original_price;
        }
        if ($request->has('terms_conditions')) {
            $promotion->terms_conditions = $request->terms_conditions;
        }
        if ($request->has('location')) {
            $promotion->location = $request->location;
        }
        if ($request->has('category')) {
            $promotion->category = $request->category;
        }
        if ($request->has('start_time')) {
            $promotion->start_time = $request->start_time;
        }
        if ($request->has('end_time')) {
            $promotion->end_time = $request->end_time;
        }
        if ($request->has('available_seats')) {
            $promotion->available_seats = $request->available_seats;
        }

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('promotions', 'public');
            $promotion->photo_path = $path;
        }

        $promotion->is_approved = false;
        $promotion->save();

        Notification::create([
            'user_id' => 1, // Asumsi admin ID 1
            'type' => 'promo_updated',
            'message' => "Promosi '{$promotion->title}' dari {$merchant->business_name} diperbarui, menunggu persetujuan.",
        ]);

        return response()->json([
            'message' => 'Promosi berhasil diperbarui, menunggu persetujuan admin.',
            'promotion' => $promotion
        ]);
    }

    /**
     * Menampilkan promosi milik merchant.
     */
    public function getOwnPromotions()
    {
        $merchant = Auth::user()->merchant;
        if (!$merchant) {
            return response()->json(['message' => 'Anda bukan merchant.'], 403);
        }

        $promotions = $merchant->promotions()->get();
        return response()->json($promotions);
    }

    /**
     * Menampilkan daftar pesanan user.
     */
    public function getOrders()
    {
        $merchant = Auth::user()->merchant;
        if (!$merchant) {
            return response()->json(['message' => 'Anda bukan merchant.'], 403);
        }

        $orders = Voucher::whereIn('promotion_id', $merchant->promotions->pluck('id'))
            ->with('user', 'promotion')
            ->get();

        return response()->json($orders);
    }

    /**
     * Redeem voucher.
     */
    public function redeemVoucher(Request $request)
    {
        $request->validate(['voucher_code' => 'required|string']);

        $merchant = Auth::user()->merchant;
        if (!$merchant) {
            return response()->json(['message' => 'Anda bukan merchant.'], 403);
        }

        $voucher = Voucher::with('promotion')->where('code', $request->voucher_code)->first();
        if (!$voucher) {
            return response()->json(['message' => 'Voucher tidak ditemukan.'], 404);
        }
        if ($voucher->is_redeemed || $voucher->status === 'used') {
            return response()->json(['message' => 'Voucher sudah digunakan.'], 409);
        }
        if (!$voucher->is_paid) {
            return response()->json(['message' => 'Voucher belum dibayar.'], 400);
        }
        if ($voucher->promotion->merchant_id !== $merchant->id) {
            return response()->json(['message' => 'Voucher ini bukan untuk merchant Anda.'], 403);
        }

        $voucher->is_redeemed = true;
        $voucher->status = 'used';
        $voucher->redeemed_at = now();
        $voucher->save();

        Notification::create([
            'user_id' => $voucher->user_id,
            'type' => 'voucher_redeemed',
            'message' => "Terima kasih telah menggunakan voucher {$voucher->code} di {$voucher->promotion->title}!",
        ]);

        return response()->json(['message' => 'Voucher berhasil diredeem.']);
    }

    /**
     * Konfirmasi pembayaran WhatsApp.
     */
    public function confirmWhatsAppPayment(Request $request)
    {
        $request->validate([
            'voucher_id' => 'required|exists:vouchers,id',
            'proof_path' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $merchant = Auth::user()->merchant;
        if (!$merchant) {
            return response()->json(['message' => 'Anda bukan merchant.'], 403);
        }

        $voucher = Voucher::with('promotion')->find($request->voucher_id);
        if (!$voucher || $voucher->payment_method !== 'whatsapp' || $voucher->promotion->merchant_id !== $merchant->id) {
            return response()->json(['message' => 'Voucher tidak valid atau bukan milik merchant Anda.'], 403);
        }

        if ($voucher->is_paid) {
            return response()->json(['message' => 'Voucher sudah dibayar.'], 409);
        }

        $proofPath = $request->file('proof_path')->store('proofs', 'public');
        $voucher->proof_path = $proofPath;
        $voucher->is_paid = true;
        $voucher->save();

        Notification::create([
            'user_id' => $voucher->user_id,
            'type' => 'payment_confirmed',
            'message' => "Pembayaran untuk voucher {$voucher->code} telah dikonfirmasi oleh merchant.",
        ]);

        return response()->json(['message' => 'Pembayaran WhatsApp berhasil dikonfirmasi.']);
    }
}