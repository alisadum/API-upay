<?php

use App\Models\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MerchantController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\MidtransCallbackController;

Route::get('/sanctum/csrf-cookie', [\Laravel\Sanctum\Http\Controllers\CsrfCookieController::class, 'show']);

// AUTH (public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/register/merchant', [AuthController::class, 'registerMerchant']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/promotions', [UserController::class, 'viewPromotions']);

// PROTECTED
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'getUser']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- User Routes ---
    Route::middleware('role:user|merchant|admin')->group(function () {
        Route::post('/promotions/purchase', [UserController::class, 'purchasePromotion']);
        Route::get('/my-vouchers', [UserController::class, 'viewMyVouchers']);
        Route::get('/vouchers/{voucher}', [UserController::class, 'showVoucherCode']);
        Route::post('/vouchers/{voucher}/book', [UserController::class, 'bookSchedule']);
        Route::get('/points', [UserController::class, 'viewPoints']);
        Route::post('/wallet/topup', [UserController::class, 'topUpWallet']);
        Route::get('/wallet', [UserController::class, 'viewWallet']);
        Route::get('/wallet/transactions', [UserController::class, 'viewWalletTransactions']);
    });

    // --- Merchant Routes ---
    Route::middleware(['auth:sanctum'])->prefix('merchant')->group(function () {
    Route::get('/profile', [MerchantController::class, 'profile']);
    Route::put('/profile', [MerchantController::class, 'updateProfile']); // Ubah ke PUT
    Route::post('/promotions', [MerchantController::class, 'createPromotion']);
    Route::put('/promotions/{promotion}', [MerchantController::class, 'updatePromotion']);
    Route::delete('/promotions/{promotion}', [MerchantController::class, 'deletePromotion']); // Tambah prefix merchant
    Route::get('/my-promotions', [MerchantController::class, 'getOwnPromotions']);
    Route::get('/promotions/{promotion}', [MerchantController::class, 'showPromotion']);
    Route::get('/orders', [MerchantController::class, 'getOrders']);
    Route::post('/redeem-voucher', [MerchantController::class, 'redeemVoucher']);
    Route::post('/confirm-whatsapp-payment', [MerchantController::class, 'confirmWhatsAppPayment']);
    Route::get('/outlets', [MerchantController::class, 'getOutlets']);
    Route::post('/outlets', [MerchantController::class, 'createOutlet']);
    Route::put('/outlets/{id}', [MerchantController::class, 'updateOutlet']); // Ubah ke PUT
    Route::delete('/outlets/{id}', [MerchantController::class, 'deleteOutlet']);
});

    // --- Admin Routes ---
    Route::middleware('role:admin')->group(function () {
        Route::post('/admin/users/{user}/assign-role', [AdminController::class, 'assignRole']);
        Route::get('/admin/merchants/pending', [AdminController::class, 'viewPendingMerchants']);
        Route::post('/admin/merchants/{merchant}/approve', [AdminController::class, 'approveMerchant']);
        Route::get('/admin/promotions/pending', [AdminController::class, 'viewPendingPromotions']);
        Route::post('/admin/promotions/{promotion}/approve', [AdminController::class, 'approvePromotion']);
        Route::post('/admin/promotions/{promotion}/reject', [AdminController::class, 'rejectPromotion']);
        Route::get('/admin/users', [AdminController::class, 'viewAllUsers']);
        Route::get('/admin/pending-outlets', [AdminController::class, 'viewPendingOutlets']);
        Route::post('/admin/outlets/{outlet}/approve', [AdminController::class, 'approveOutlet']);
        Route::post('/admin/outlets/{outlet}/reject', [AdminController::class, 'rejectOutlet']);
        Route::get('/admin/users', [AdminController::class, 'viewAllUsers']);
        Route::post('/admin/users/{user}/assign-role', [AdminController::class, 'assignRole']);
    });

    Route::get('/notifications', function () {
        $notifications = Notification::where('user_id', Auth::id())
            ->latest()
            ->get();
        return response()->json($notifications);
    });

    Route::get('/categories', function () {
        return response()->json(\App\Models\Category::all());
    });
});

// Midtrans callback
Route::post('/midtrans/callback', [MidtransCallbackController::class, 'handle']);
