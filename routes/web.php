<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\WooCommerceWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/apps/{product:slug}', [StorefrontController::class, 'product'])->name('products.show');
Route::get('/authors/{author:slug}', [StorefrontController::class, 'author'])->name('authors.show');
Route::get('/submit', [StorefrontController::class, 'submit'])->name('submissions.create');
Route::post('/submit', [StorefrontController::class, 'storeSubmission'])->middleware('throttle:public-form')->name('submissions.store');
Route::get('/checkout/{product}', [StorefrontController::class, 'checkout'])->name('checkout.show');
Route::post('/checkout/{product}', [StorefrontController::class, 'startCheckout'])->name('checkout.start');
Route::get('/orders/lookup', [StorefrontController::class, 'lookup'])->name('orders.lookup');
Route::post('/orders/lookup', [StorefrontController::class, 'lookupResult'])->middleware('throttle:public-form')->name('orders.lookup.result');
Route::post('/webhooks/woocommerce', WooCommerceWebhookController::class)->middleware('throttle:webhooks')->name('webhooks.woocommerce');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:public-form');
    Route::get('/forgot-password', [AuthController::class, 'forgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'resetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/email/verify', [VerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [VerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');
    Route::get('/account/purchases', [StorefrontController::class, 'purchases'])->name('account.purchases');
});

Route::get('/downloads/{version}/{license}', DownloadController::class)
    ->middleware('signed')
    ->name('downloads.show');
