<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('user');

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil! Selamat datang.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->only('id', 'name', 'email')
        ], 201);
    }

    public function registerMerchant(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'business_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('merchant');
        Merchant::create([
            'user_id' => $user->id,
            'business_name' => $request->business_name,
            'address' => $request->address,
            'is_approved' => false,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Pendaftaran merchant berhasil! Menunggu persetujuan admin.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->only('id', 'name', 'email')
        ]);
    }

    public function login(Request $request)
    {
        $request->validate(['email' => 'required|string|email', 'password' => 'required|string']);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages(['email' => ['Kredensial yang diberikan tidak cocok.']]);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil! Selamat datang kembali.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->only('id', 'name', 'email')
        ]);
    }

    public function getUser(Request $request)
    {
        return $request->user();
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Berhasil logout.']);
    }
}