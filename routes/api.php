<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\PunguzoAuthController;
use App\Http\Controllers\PunguzoPaymentController;
use App\Http\Controllers\PunguzoProductController;
use App\Http\Controllers\Api\FCMController;
use App\Http\Controllers\Api\MessagingController;
use App\Http\Controllers\Api\ConfigController;

// Public Routes
Route::get('/config/business', [ConfigController::class, 'getBusinessConfig']);
Route::prefix('auth')->group(function () {
    Route::post('/check-phone', [AuthController::class, 'checkPhone']);
    Route::post('/verify-sponsor', [AuthController::class, 'verifySponsor']);
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
});

// Products (Public) - Order matters: specific routes before parameterized ones
Route::get('/products/grouped-by-category', [ProductController::class, 'groupedByCategory']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    // Wallet
    Route::prefix('wallet')->group(function () {
        Route::get('/balance', [WalletController::class, 'balance']);
        Route::post('/pay', [WalletController::class, 'pay']);
        Route::post('/add-funds', [WalletController::class, 'addFunds']);
        Route::get('/transactions', [WalletController::class, 'transactions']);
        Route::post('/transfer', [WalletController::class, 'transfer']);
        Route::post('/withdraw', [WalletController::class, 'withdraw']);
    });

    // Team - Complete MLM functionality
    Route::prefix('team')->group(function () {
        Route::get('/members', [TeamController::class, 'members']);
        Route::get('/upliner', [TeamController::class, 'upliner']);
        Route::get('/performance', [TeamController::class, 'performance']);
        Route::post('/downliner', [TeamController::class, 'addDownliner']);
        Route::get('/search', [TeamController::class, 'search']);
        Route::get('/member/{memberId}/performance', [TeamController::class, 'memberPerformance']);
        Route::post('/message', [TeamController::class, 'sendMessage']);
        
        // MLM specific endpoints
        Route::post('/referral-code', [TeamController::class, 'generateReferralCode']);
        Route::post('/qr-code', [TeamController::class, 'generateQRCode']);
        Route::get('/hierarchy', [TeamController::class, 'hierarchy']);
        Route::get('/commission-history', [TeamController::class, 'commissionHistory']);
        Route::post('/withdraw-commission', [TeamController::class, 'withdrawCommission']);
        Route::get('/analytics', [TeamController::class, 'analytics']);
        Route::get('/product-catalog', [TeamController::class, 'productCatalog']);
        Route::post('/share-link', [TeamController::class, 'generateShareLink']);
        Route::get('/commission-rules', [TeamController::class, 'commissionRules']);
        Route::put('/mlm-settings', [TeamController::class, 'updateMLMSettings']);
        Route::get('/notifications', [TeamController::class, 'notifications']);
        Route::put('/notifications/{notificationId}/read', [TeamController::class, 'markNotificationRead']);
    });

    // Support
    Route::post('/support/feedback', [SupportController::class, 'feedback']);

    // FCM Routes
    Route::prefix('fcm')->group(function () {
        Route::post('token', [FCMController::class, 'updateToken']);
        Route::post('subscribe', [FCMController::class, 'subscribeToTopic']);
        Route::post('unsubscribe', [FCMController::class, 'unsubscribeFromTopic']);
    });
});

// Punguzo Integration Routes
Route::prefix('punguzo')->group(function () {
    // Authentication
    Route::post('/auth/token', [PunguzoAuthController::class, 'generateToken']);

    // Products
    Route::get('/products', [PunguzoProductController::class, 'getProducts']);

    // Payments
    Route::post('/payments/debit', [PunguzoPaymentController::class, 'debitRequest']);
    Route::post('/payments/callback', [PunguzoPaymentController::class, 'paymentCallback']);
});

// Messaging API Routes (Protected with Sanctum)
Route::middleware('auth:sanctum')->prefix('messaging')->group(function () {
    Route::post('/send', [MessagingController::class, 'send']);
    Route::post('/send-bulk', [MessagingController::class, 'sendBulk']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
