<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;

/*
|--------------------------------------------------------------------------
| Web Routes - PHP/Blade Frontend
|--------------------------------------------------------------------------
*/

// Public Pages
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/legal/{slug}', [PageController::class, 'legal'])->name('legal');

// Payment Status Pages
Route::get('/payment/success', [PageController::class, 'paymentSuccess'])->name('payment.success');
Route::get('/payment/failed', [PageController::class, 'paymentFailed'])->name('payment.failed');
Route::get('/success', [PageController::class, 'paymentSuccess']);
Route::get('/failed', [PageController::class, 'paymentFailed']);
Route::get('/order/success', [PageController::class, 'paymentSuccess']);
Route::get('/order/fail', [PageController::class, 'paymentFailed']);

// Account Pages (Client-side auth check)
Route::prefix('account')->group(function () {
    Route::get('/', [PageController::class, 'account'])->name('account');
    Route::get('/orders', [PageController::class, 'accountOrders'])->name('account.orders');
    Route::get('/orders/{orderId}', [PageController::class, 'accountOrderDetail'])->name('account.order-detail');
    Route::get('/profile', [PageController::class, 'accountProfile'])->name('account.profile');
    Route::get('/security', [PageController::class, 'accountSecurity'])->name('account.security');
    Route::get('/support', [PageController::class, 'supportTickets'])->name('account.support');
    Route::get('/support/new', [PageController::class, 'supportNewTicket'])->name('account.support.new');
    Route::get('/support/{ticketId}', [PageController::class, 'supportTicketDetail'])->name('account.support.detail');
});

// Admin Pages (Client-side auth check)
Route::prefix('admin')->group(function () {
    Route::get('/login', [PageController::class, 'adminLogin'])->name('admin.login');
    Route::get('/dashboard', [PageController::class, 'adminDashboard'])->name('admin.dashboard');
    Route::get('/orders', [PageController::class, 'adminOrders'])->name('admin.orders');
    Route::get('/orders/{orderId}', [PageController::class, 'adminOrders'])->name('admin.order-detail');
    Route::get('/products', [PageController::class, 'adminProducts'])->name('admin.products');
    Route::get('/support', [PageController::class, 'adminSupport'])->name('admin.support');
    Route::get('/support/{ticketId}', [PageController::class, 'adminSupport'])->name('admin.support.detail');
    Route::get('/settings', [PageController::class, 'adminSettings'])->name('admin.settings');
    Route::get('/settings/{tab}', [PageController::class, 'adminSettings'])->name('admin.settings.tab');
});