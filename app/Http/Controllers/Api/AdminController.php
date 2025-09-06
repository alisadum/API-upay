<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Promotion;
use App\Models\Outlet;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function assignRole(Request $request, User $user)
    {
        $request->validate(['role_name' => 'required|string|exists:roles,name']);
        $user->syncRoles($request->role_name);
        return response()->json(['message' => "Peran '{$user->email}' jadi '{$request->role_name}' bro."]);
    }

    public function viewPendingMerchants()
    {
        $merchants = Merchant::where('is_approved', false)->get();
        return response()->json($merchants);
    }

    public function approveMerchant(Merchant $merchant)
    {
        if ($merchant->is_approved) {
            return response()->json(['message' => 'Merchant ini udah disetujui bro.'], 409);
        }
        $merchant->is_approved = true;
        $merchant->save();

        Notification::create([
            'user_id' => $merchant->user_id,
            'type' => 'merchant_approved',
            'message' => "Merchant {$merchant->business_name} udah disetujui admin!",
        ]);

        return response()->json(['message' => 'Merchant disetujui bro!']);
    }

    public function viewPendingPromotions()
    {
        $promotions = Promotion::where('is_approved', false)->with('merchant')->get();
        return response()->json($promotions);
    }

    public function approvePromotion(Promotion $promotion)
    {
        if ($promotion->is_approved) {
            return response()->json(['message' => 'Promo ini udah disetujui bro.'], 409);
        }
        $promotion->is_approved = true;
        $promotion->reject_reason = null;
        $promotion->save();

        Notification::create([
            'user_id' => $promotion->merchant->user_id,
            'type' => 'promo_approved',
            'message' => "Promo {$promotion->title} udah disetujui admin!",
        ]);

        return response()->json(['message' => 'Promo disetujui bro!']);
    }

    public function rejectPromotion(Request $request, Promotion $promotion)
    {
        $request->validate(['reject_reason' => 'required|string']);

        if ($promotion->is_approved) {
            return response()->json(['message' => 'Promo ini udah disetujui, ga bisa ditolak bro.'], 409);
        }

        $promotion->reject_reason = $request->reject_reason;
        $promotion->save();

        Notification::create([
            'user_id' => $promotion->merchant->user_id,
            'type' => 'promo_rejected',
            'message' => "Promo {$promotion->title} ditolak admin. Alasan: {$request->reject_reason}",
        ]);

        return response()->json(['message' => 'Promo ditolak bro, alasan udah dikirim.']);
    }

    public function viewPendingOutlets()
    {
        $outlets = Outlet::where('is_approved', false)->with('merchant')->get();
        return response()->json($outlets);
    }

    public function approveOutlet(Outlet $outlet)
    {
        if ($outlet->is_approved) {
            return response()->json(['message' => 'Outlet ini udah disetujui bro.'], 409);
        }
        $outlet->is_approved = true;
        $outlet->reject_reason = null;
        $outlet->save();

        Notification::create([
            'user_id' => $outlet->merchant->user_id,
            'type' => 'outlet_approved',
            'message' => "Outlet {$outlet->name} udah disetujui admin!",
        ]);

        return response()->json(['message' => 'Outlet disetujui bro!']);
    }

    public function rejectOutlet(Request $request, Outlet $outlet)
    {
        $request->validate(['reject_reason' => 'required|string']);

        if ($outlet->is_approved) {
            return response()->json(['message' => 'Outlet ini udah disetujui, ga bisa ditolak bro.'], 409);
        }

        $outlet->reject_reason = $request->reject_reason;
        $outlet->save();

        Notification::create([
            'user_id' => $outlet->merchant->user_id,
            'type' => 'outlet_rejected',
            'message' => "Outlet {$outlet->name} ditolak admin. Alasan: {$request->reject_reason}",
        ]);

        return response()->json(['message' => 'Outlet ditolak bro, alasan udah dikirim.']);
    }

    public function viewAllUsers()
    {
        $users = User::with('roles')->get();
        return response()->json($users);
    }
}