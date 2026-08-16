<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/search', [StorefrontController::class, 'search'])->name('search');
Route::prefix('store')->name('store.')->controller(StoreController::class)->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::get('/new-arrivals', 'newArrivals')->name('newarrivals');
    Route::get('/best-sellers', 'bestsellers')->name('bestsellers');
    Route::get('/deals', 'deals')->name('deals');
});
Route::get('/apps/{slug}', [StorefrontController::class, 'product'])->name('products.show');
Route::get('/vendors', [StorefrontController::class, 'vendors'])->name('vendors.index');
Route::get('/vendors/{slug}', [StorefrontController::class, 'vendor'])->name('vendors.show');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/{slug}', [CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/{cartLine}', [CartController::class, 'update'])->whereNumber('cartLine')->name('cart.update');
Route::delete('/cart/{cartLine}', [CartController::class, 'destroy'])->whereNumber('cartLine')->name('cart.destroy');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:public-form');
    Route::get('/forgot-password', [AuthController::class, 'forgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'resetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/email/verify', [VerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [VerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');
    Route::get('/account', fn () => redirect()->route('account.settings'))->name('account');
    Route::get('/account/orders', [AccountController::class, 'orders'])->name('account.orders');
    Route::get('/account/purchases', [AccountController::class, 'orders'])->name('account.purchases');
    Route::get('/account/settings', [AccountController::class, 'settings'])->name('account.settings');
    Route::patch('/account/settings', [AccountController::class, 'update'])->name('account.settings.update');

    Route::middleware('verified')->group(function (): void {
        Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
        Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
        Route::get('/checkout/complete/{order}', [CheckoutController::class, 'complete'])->name('checkout.complete');
    });
});
