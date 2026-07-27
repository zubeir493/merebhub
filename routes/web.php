<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ChapaWebhookController;
use App\Http\Controllers\CheckoutReturnController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/search', [StorefrontController::class, 'search'])->name('search');
Route::prefix('store')->name('store.')->controller(StoreController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/new-arrivals', 'newArrivals')->name('newarrivals');
    Route::get('/best-sellers', 'bestsellers')->name('bestsellers');
    Route::get('/deals', 'deals')->name('deals');
});
Route::get('/apps/{product:slug}', [StorefrontController::class, 'product'])->name('products.show');
Route::get('/vendors', [StorefrontController::class, 'vendors'])->name('vendors.index');
Route::get('/vendors/{author:slug}', [StorefrontController::class, 'vendor'])->name('vendors.show');
Route::redirect('/authors/{author}', '/vendors/{author}', 301);
Route::get('/submit', [StorefrontController::class, 'submit'])->name('submissions.create');
Route::post('/submit', [StorefrontController::class, 'storeSubmission'])->middleware('throttle:public-form')->name('submissions.store');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/{product}', [CartController::class, 'store'])->whereNumber('product')->name('cart.store');
Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
Route::get('/orders/lookup', [StorefrontController::class, 'lookup'])->name('orders.lookup');
Route::post('/orders/lookup', [StorefrontController::class, 'lookupResult'])->middleware('throttle:public-form')->name('orders.lookup.result');
Route::post('/webhooks/chapa', ChapaWebhookController::class)->middleware('throttle:webhooks')->name('webhooks.chapa');

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
    Route::post('/cart/checkout', [CartController::class, 'checkout'])->middleware('verified')->name('cart.checkout');
    Route::patch('/cart/{cartItem}', [CartController::class, 'update'])->whereNumber('cartItem')->name('cart.update');
    Route::delete('/cart/{cartItem}', [CartController::class, 'destroy'])->whereNumber('cartItem')->name('cart.destroy');
    Route::post('/wishlist/{product}', [WishlistController::class, 'store'])->whereNumber('product')->name('wishlist.store');
    Route::delete('/wishlist/{wishlistItem}', [WishlistController::class, 'destroy'])->whereNumber('wishlistItem')->name('wishlist.destroy');
    Route::get('/account', function () {
        return redirect()->route('account.settings');
    })->name('account');
    Route::get('/account/orders', [AccountController::class, 'orders'])->name('account.orders');
    Route::get('/account/purchases', [AccountController::class, 'orders'])->name('account.purchases');
    Route::get('/account/settings', [AccountController::class, 'settings'])->name('account.settings');
    Route::patch('/account/settings', [AccountController::class, 'update'])->name('account.settings.update');
    Route::get('/account/subscriptions', [SubscriptionController::class, 'index'])->name('account.subscriptions');
    Route::post('/account/subscriptions/{subscription}/renew', [SubscriptionController::class, 'renew'])->name('account.subscriptions.renew');
    Route::get('/checkout/return/{order:public_id}', CheckoutReturnController::class)->name('payments.chapa.return');
});

Route::get('/downloads/{version}/{license}', DownloadController::class)
    ->middleware(['auth', 'signed'])
    ->name('downloads.show');
