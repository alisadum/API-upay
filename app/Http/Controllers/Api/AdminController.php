<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    // Mengubah peran pengguna (misal: user biasa menjadi admin/merchant)
    public function assignRole(Request $request, User $user)
    {
        // Validasi apakah peran yang diminta valid
        $request->validate(['role_name' => 'required|string|exists:roles,name']);

        // Hapus semua peran yang ada dan tambahkan peran baru
        $user->syncRoles($request->role_name);

        return response()->json(['message' => "Peran pengguna '{$user->email}' berhasil diubah menjadi '{$request->role_name}'."]);
    }

    // Melihat daftar merchant yang belum disetujui
    public function viewPendingMerchants()
    {
        $merchants = Merchant::where('is_approved', false)->get();
        return response()->json($merchants);
    }

    // Menyetujui merchant
    public function approveMerchant(Merchant $merchant)
    {
        if ($merchant->is_approved) {
            return response()->json(['message' => 'Merchant ini sudah disetujui.'], 409);
        }
        $merchant->is_approved = true;
        $merchant->save();
        return response()->json(['message' => 'Merchant berhasil disetujui.']);
    }

    // Melihat daftar promosi yang belum disetujui
    public function viewPendingPromotions()
    {
        $promotions = Promotion::where('is_approved', false)->with('merchant')->get();
        return response()->json($promotions);
    }

    // Menyetujui promosi
    public function approvePromotion(Promotion $promotion)
    {
        if ($promotion->is_approved) {
            return response()->json(['message' => 'Promosi ini sudah disetujui.'], 409);
        }
        $promotion->is_approved = true;
        $promotion->save();
        return response()->json(['message' => 'Promosi berhasil disetujui.']);
    }

    // Melihat semua user (untuk kelola akun)
    public function viewAllUsers()
    {
        $users = User::with('roles')->get();
        return response()->json($users);
    }
}