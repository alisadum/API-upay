<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Merchant;
use App\Models\UserWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // REGISTER USER BIASA
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'points' => 0,
            'membership_level' => 'silver',
        ]);

        // Buat wallet untuk user baru
        UserWallet::create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        if (method_exists($user, 'assignRole')) {
            $user->assignRole('user');
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi user berhasil bro!',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'points' => $user->points,
                'membership_level' => $user->membership_level,
                'role' => $user->getRoleNames()->first(),
                'wallet_balance' => $user->wallet ? $user->wallet->balance : 0,
            ],
        ], 200);
    }

    // REGISTER MERCHANT
    public function registerMerchant(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'nama_brand' => 'required|string|max:255',
            'alamat' => 'required|string|max:255',
            'whatsapp' => 'required|string|max:25',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Buat wallet untuk merchant
        UserWallet::create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        if (method_exists($user, 'assignRole')) {
            $user->assignRole('merchant');
        }

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('merchants', 'public');
        }

        $merchant = Merchant::create([
            'user_id' => $user->id,
            'business_name' => $request->nama_brand,
            'address' => $request->alamat,
            'whatsapp' => $request->whatsapp,
            'photo_path' => $fotoPath,
            'is_approved' => true, // Ubah ke true agar langsung disetujui
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi merchant berhasil! Langsung bisa login bro.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
                'wallet_balance' => $user->wallet ? $user->wallet->balance : 0,
            ],
            'merchant' => [
                'id' => $merchant->id,
                'business_name' => $merchant->business_name,
                'address' => $merchant->address,
                'whatsapp' => $merchant->whatsapp,
                'photo_path' => $merchant->photo_path ? url('storage/' . $merchant->photo_path) : null,
                'is_approved' => $merchant->is_approved,
            ],
        ], 200);
    }

    // LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah bro!'],
            ]);
        }

        $user = Auth::user()->load('merchant', 'wallet');
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil, Selamat datang!',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => method_exists($user, 'getRoleNames') ? $user->getRoleNames()->first() : null,
                'wallet_balance' => $user->wallet ? $user->wallet->balance : 0,
            ],
            'merchant' => $user->merchant ? [
                'id' => $user->merchant->id,
                'business_name' => $user->merchant->business_name,
                'address' => $user->merchant->address,
                'whatsapp' => $user->merchant->whatsapp,
                'photo_path' => $user->merchant->photo_path ? url('storage/' . $user->merchant->photo_path) : null,
                'is_approved' => $user->merchant->is_approved,
            ] : null,
        ]);
    }

    // PROFIL USER
    public function getUser(Request $request)
    {
        $user = $request->user()->load('merchant', 'wallet');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => method_exists($user, 'getRoleNames') ? $user->getRoleNames()->first() : null,
            'wallet_balance' => $user->wallet ? $user->wallet->balance : 0,
            'merchant' => $user->merchant ? [
                'id' => $user->merchant->id,
                'business_name' => $user->merchant->business_name,
                'address' => $user->merchant->address,
                'whatsapp' => $user->merchant->whatsapp,
                'photo_path' => $user->merchant->photo_path ? url('storage/' . $user->merchant->photo_path) : null,
                'is_approved' => $user->merchant->is_approved,
            ] : null,
        ]);
    }

    // LOGOUT
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Berhasil logout bro!']);
    }
}