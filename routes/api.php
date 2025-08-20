<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MerchantController;
use App\Http\Controllers\Api\AdminController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register/merchant', [AuthController::class, 'registerMerchant']);

// Rute yang memerlukan autentikasi Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'getUser']); // Mendapatkan data user yang sedang login
    Route::post('/logout', [AuthController::class, 'logout']);

    // Rute untuk Sisi User (Pembeli)
    // User bisa melihat promosi jika dia adalah 'user', 'merchant', atau 'admin'
    Route::middleware('role:user|merchant|admin')->group(function () {
        Route::get('/promotions', [UserController::class, 'viewPromotions']);
        Route::post('/promotions/purchase', [UserController::class, 'purchasePromotion']);
        Route::get('/my-vouchers', [UserController::class, 'viewMyVouchers']);
        Route::get('/vouchers/{voucher}', [UserController::class, 'showVoucherCode']); // Untuk melihat kode voucher spesifik
    });

    // Rute untuk Sisi Merchant
    // Hanya user dengan peran 'merchant' atau 'admin' yang bisa mengakses ini
    Route::middleware('role:merchant|admin')->group(function () {
        Route::post('/merchant/register', [MerchantController::class, 'registerAsMerchant']); // User mendaftar sebagai merchant
        Route::post('/merchant/promotions', [MerchantController::class, 'createPromotion']); // Merchant membuat promo
        Route::get('/merchant/my-promotions', [MerchantController::class, 'viewOwnPromotions']); // Merchant melihat promo sendiri
        Route::post('/merchant/redeem-voucher', [MerchantController::class, 'redeemUserVoucher']); // Merchant meredeem voucher user
    });

    // Rute untuk Sisi Admin
    // Hanya user dengan peran 'admin' yang bisa mengakses ini
    Route::middleware('role:admin')->group(function () {
        Route::post('/admin/users/{user}/assign-role', [AdminController::class, 'assignRole']); // Mengubah peran user
        Route::get('/admin/merchants/pending', [AdminController::class, 'viewPendingMerchants']); // Melihat merchant yang belum disetujui
        Route::post('/admin/merchants/{merchant}/approve', [AdminController::class, 'approveMerchant']); // Menyetujui merchant
        Route::get('/admin/promotions/pending', [AdminController::class, 'viewPendingPromotions']); // Melihat promosi yang belum disetujui
        Route::post('/admin/promotions/{promotion}/approve', [AdminController::class, 'approvePromotion']); // Menyetujui promosi
        Route::get('/admin/users', [AdminController::class, 'viewAllUsers']); // Melihat semua pengguna
    });
});
