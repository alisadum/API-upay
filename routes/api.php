<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MerchantController;
use App\Http\Controllers\Api\AdminController;


// AUTH (public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/register/merchant', [AuthController::class, 'registerMerchant']);
Route::post('/login', [AuthController::class, 'login']);


// PROTECTED
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'getUser']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- User (Pembeli) Routes ---
    Route::middleware('role:user|merchant|admin')->group(function () {
        Route::get('/promotions', [UserController::class, 'viewPromotions']);
        Route::post('/promotions/purchase', [UserController::class, 'purchasePromotion']);
        Route::get('/my-vouchers', [UserController::class, 'viewMyVouchers']);
        Route::get('/vouchers/{voucher}', [UserController::class, 'showVoucherCode']);
    });

    // --- Merchant Routes ---
    Route::get('/merchant/profile', [MerchantController::class, 'profile']);
    Route::post('/merchant/profile', [MerchantController::class, 'updateProfile']);
    Route::post('/merchant/promotions', [MerchantController::class, 'createPromotion']);
    Route::get('/merchant/my-promotions', [MerchantController::class, 'getOwnPromotions']);
    Route::post('/merchant/redeem-voucher', [MerchantController::class, 'redeemVoucher']);

    // --- Admin Routes ---
    Route::middleware('role:admin')->group(function () {
        Route::post('/admin/users/{user}/assign-role', [AdminController::class, 'assignRole']);
        Route::get('/admin/merchants/pending', [AdminController::class, 'viewPendingMerchants']);
        Route::post('/admin/merchants/{merchant}/approve', [AdminController::class, 'approveMerchant']);
        Route::get('/admin/promotions/pending', [AdminController::class, 'viewPendingPromotions']);
        Route::post('/admin/promotions/{promotion}/approve', [AdminController::class, 'approvePromotion']);
        Route::get('/admin/users', [AdminController::class, 'viewAllUsers']);
    });
});
