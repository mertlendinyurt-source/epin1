<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\SupportController as AdminSupportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UploadController;

/*
|--------------------------------------------------------------------------
| API Routes - PINLY UC Store
|--------------------------------------------------------------------------
| Birebir Next.js API routes ile uyumlu
*/

// ==========================================
// PUBLIC ROUTES (No Auth Required)
// ==========================================

// Health & Root
Route::get('/', [PublicController::class, 'index']);
Route::get('/health', [PublicController::class, 'health']);

// Products
Route::get('/products', [PublicController::class, 'products']);

// Player Resolver (RapidAPI)
Route::get('/player/resolve', [PlayerController::class, 'resolve'])->middleware('throttle.custom');

// Site Settings (Public)
Route::get('/site/settings', [PublicController::class, 'siteSettings']);
Route::get('/site/banner', [PublicController::class, 'bannerSettings']);

// Regions
Route::get('/regions', [PublicController::class, 'regions']);

// Content & Reviews
Route::get('/content/pubg', [PublicController::class, 'gameContent']);
Route::get('/reviews', [PublicController::class, 'reviews']);

// Legal Pages
Route::get('/legal/{slug}', [PublicController::class, 'legalPage']);

// Footer & SEO
Route::get('/footer-settings', [PublicController::class, 'footerSettings']);
Route::get('/seo/settings', [PublicController::class, 'seoSettings']);

// ==========================================
// AUTH ROUTES
// ==========================================

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle.custom');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle.custom');
    
    // Google OAuth
    Route::get('/google', [AuthController::class, 'googleAuth']);
    Route::get('/google/callback', [AuthController::class, 'googleCallback']);
    Route::get('/google/status', [AuthController::class, 'googleStatus']);
});

// Site Base URL
Route::get('/site/base-url', [PublicController::class, 'baseUrl']);

// ==========================================
// USER ROUTES (Auth Required)
// ==========================================

Route::middleware('auth.jwt')->group(function () {
    // Account
    Route::prefix('account')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'changePassword']);
        Route::put('/me', [AuthController::class, 'updateMe']);
        Route::delete('/me', [AuthController::class, 'deleteAccount']);
        
        // Orders
        Route::get('/orders', [OrderController::class, 'userOrders']);
        Route::get('/orders/recent', [OrderController::class, 'recentOrders']);
        Route::get('/orders/{orderId}', [OrderController::class, 'userOrderDetail']);
    });
    
    // Orders
    Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle.custom');
    
    // Support Tickets
    Route::prefix('support')->group(function () {
        Route::get('/tickets', [SupportController::class, 'index']);
        Route::post('/tickets', [SupportController::class, 'store'])->middleware('throttle.custom');
        Route::get('/tickets/{ticketId}', [SupportController::class, 'show']);
        Route::post('/tickets/{ticketId}/messages', [SupportController::class, 'sendMessage']);
    });
});

// ==========================================
// PAYMENT CALLBACKS (No Auth - Signature Validation)
// ==========================================

Route::prefix('payment')->group(function () {
    Route::post('/shopier/callback', [OrderController::class, 'shopierCallback']);
});

// ==========================================
// ADMIN ROUTES
// ==========================================

// Admin Login (No Auth)
Route::post('/admin/login', [AuthController::class, 'adminLogin'])->middleware('throttle.custom');

// Admin Protected Routes
Route::prefix('admin')->middleware('auth.admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/system-status', [DashboardController::class, 'systemStatus']);
    Route::get('/audit-logs', [DashboardController::class, 'auditLogs']);
    
    // Orders
    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::get('/orders/{orderId}', [AdminOrderController::class, 'show']);
    Route::post('/orders/{orderId}/approve', [AdminOrderController::class, 'approve']);
    Route::post('/orders/{orderId}/refund', [AdminOrderController::class, 'refund']);
    Route::put('/orders/{orderId}/delivery', [AdminOrderController::class, 'updateDelivery']);
    
    // Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{productId}', [ProductController::class, 'update']);
    Route::delete('/products/{productId}', [ProductController::class, 'destroy']);
    Route::get('/products/{productId}/stock', [ProductController::class, 'getStock']);
    Route::post('/products/{productId}/stock', [ProductController::class, 'addStock']);
    
    // Support
    Route::prefix('support')->group(function () {
        Route::get('/tickets', [AdminSupportController::class, 'index']);
        Route::get('/tickets/{ticketId}', [AdminSupportController::class, 'show']);
        Route::post('/tickets/{ticketId}/messages', [AdminSupportController::class, 'sendMessage']);
        Route::post('/tickets/{ticketId}/close', [AdminSupportController::class, 'close']);
    });
    
    // Settings
    Route::prefix('settings')->group(function () {
        // Site
        Route::get('/site', [SettingsController::class, 'getSiteSettings']);
        Route::post('/site', [SettingsController::class, 'updateSiteSettings']);
        
        // Payment (Shopier)
        Route::get('/payments', [SettingsController::class, 'getPaymentSettings']);
        Route::post('/payments', [SettingsController::class, 'updatePaymentSettings'])->middleware('throttle.custom');
        
        // OAuth (Google)
        Route::get('/oauth/google', [SettingsController::class, 'getOAuthSettings']);
        Route::post('/oauth/google', [SettingsController::class, 'updateOAuthSettings']);
        
        // Regions
        Route::get('/regions', [SettingsController::class, 'getRegions']);
        Route::put('/regions', [SettingsController::class, 'updateRegions']);
        
        // SEO
        Route::get('/seo', [SettingsController::class, 'getSeoSettings']);
        Route::post('/seo', [SettingsController::class, 'updateSeoSettings']);
    });
    
    // Email
    Route::prefix('email')->group(function () {
        Route::get('/settings', [SettingsController::class, 'getEmailSettings']);
        Route::post('/settings', [SettingsController::class, 'updateEmailSettings']);
        Route::post('/test', [SettingsController::class, 'testEmail']);
        Route::get('/logs', [SettingsController::class, 'getEmailLogs']);
    });
    
    // Legal Pages
    Route::get('/legal-pages', [SettingsController::class, 'getLegalPages']);
    Route::post('/legal-pages', [SettingsController::class, 'createLegalPage']);
    Route::put('/legal-pages/{pageId}', [SettingsController::class, 'updateLegalPage']);
    Route::delete('/legal-pages/{pageId}', [SettingsController::class, 'deleteLegalPage']);
    
    // Reviews
    Route::get('/reviews', [SettingsController::class, 'getReviews']);
    Route::post('/reviews', [SettingsController::class, 'createReview']);
    Route::put('/reviews/{reviewId}', [SettingsController::class, 'updateReview']);
    Route::delete('/reviews/{reviewId}', [SettingsController::class, 'deleteReview']);
    
    // Content
    Route::get('/content/pubg', [SettingsController::class, 'getGameContent']);
    Route::post('/content/pubg', [SettingsController::class, 'updateGameContent']);
    
    // Footer
    Route::get('/footer-settings', [SettingsController::class, 'getFooterSettings']);
    Route::post('/footer-settings', [SettingsController::class, 'updateFooterSettings']);
});