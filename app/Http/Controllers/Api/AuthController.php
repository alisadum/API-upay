<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Merchant;
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
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|string|email|max:255|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Spatie role
        if (method_exists($user, 'assignRole')) {
            $user->assignRole('user');
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'Registrasi user berhasil!',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user->only('id', 'name', 'email')
        ], 201);
    }

    // REGISTER MERCHANT (brand, alamat, foto, whatsapp wajib)
    public function registerMerchant(Request $request)
    {
        $request->validate([
            // akun
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|string|email|max:255|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
            // merchant
            'nama_brand'            => 'required|string|max:255',
            'alamat'                => 'required|string|max:255',
            'whatsapp'              => 'required|string|max:25',
            'foto'                  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if (method_exists($user, 'assignRole')) {
            $user->assignRole('merchant');
        }

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('merchants', 'public');
        }

        $merchant = Merchant::create([
            'user_id'    => $user->id,
            'business_name' => $request->nama_brand, // atau kolom 'nama_brand' sesuai migrasi kamu
            'address'    => $request->alamat,
            'whatsapp'   => $request->whatsapp,
            'photo_path' => $fotoPath,
            'is_approved'=> false, // nunggu admin approve
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'Registrasi merchant berhasil! Menunggu persetujuan admin.',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user->only('id', 'name', 'email'),
            'merchant'     => $merchant->only('id','business_name','address','whatsapp','photo_path','is_approved')
        ], 201);
    }

    // LOGIN (email + password)
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan tidak cocok.'],
            ]);
        }

        $user  = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'Login berhasil!',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => method_exists($user, 'getRoleNames') ? $user->getRoleNames()->first() : null,
            ],
            'merchant' => $user->relationLoaded('merchant') ? $user->merchant : ($user->loadMissing('merchant')->merchant ?? null),
        ]);
    }

    // PROFIL SINGKAT USER (plus status merchant jika ada)
    public function getUser(Request $request)
    {
        $user = $request->user()->loadMissing('merchant');

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => method_exists($user, 'getRoleNames') ? $user->getRoleNames()->first() : null,
            'merchant' => $user->merchant ? [
                'id'          => $user->merchant->id,
                'business_name'=> $user->merchant->business_name,
                'is_approved' => $user->merchant->is_approved,
            ] : null
        ]);
    }

    // LOGOUT
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Berhasil logout.']);
    }
}
