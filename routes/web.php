<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingProfileController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ChapaPaymentController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CredentialController;
use App\Http\Controllers\DemoOfflineActivationController;
use App\Http\Controllers\DeveloperController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OfflineLicenseController;
use App\Http\Controllers\SecuritySessionController;
use App\Http\Controllers\StaffSupportController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/health', [HealthController::class, 'shallow'])->name('health');
Route::middleware('auth')->get('/health/deep', [HealthController::class, 'deep'])->name('health.deep');
Route::get('/app-shell', fn () => Inertia::render('PlatformShell'))->name('app-shell');
Route::get('/search', [StoreController::class, 'search'])->name('search');
Route::controller(DeveloperController::class)->group(function (): void {
    Route::get('/developers', 'index')->name('developers.index');
    Route::post('/developers/apply', 'store')->middleware('throttle:public-form')->name('developers.apply');
});
Route::controller(ContactController::class)->group(function (): void {
    Route::get('/contact', 'index')->name('contact.index');
    Route::post('/contact', 'store')->middleware('throttle:public-form')->name('contact.store');
});
Route::get('/offline-activation', [DemoOfflineActivationController::class, 'show'])->name('offline-activation.show');
Route::post('/offline-activation', [DemoOfflineActivationController::class, 'generate'])
    ->middleware('throttle:10,1')
    ->name('offline-activation.generate');
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
Route::get('/cart/mini', [CartController::class, 'mini'])->name('cart.mini');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');
Route::post('/cart/share', [CartController::class, 'share'])->name('cart.share');
Route::get('/cart/shared', [CartController::class, 'shared'])->middleware('signed')->name('cart.shared');
Route::post('/cart/{slug}', [CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/{cartLine}', [CartController::class, 'update'])->whereNumber('cartLine')->name('cart.update');
Route::delete('/cart/{cartLine}', [CartController::class, 'destroy'])->whereNumber('cartLine')->name('cart.destroy');
Route::get('/payments/chapa/callback', [ChapaPaymentController::class, 'return'])->name('payments.chapa.callback');
Route::get('/payments/chapa/return', [ChapaPaymentController::class, 'return'])->name('payments.chapa.return');
Route::post('/payments/chapa/webhook', [ChapaPaymentController::class, 'webhook'])->name('payments.chapa.webhook');

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
    Route::get('/account/wishlist', [WishlistController::class, 'index'])->name('account.wishlist');
    Route::match(['get', 'post'], '/account/wishlist/{slug}', [WishlistController::class, 'store'])->name('wishlist.store');
    Route::delete('/account/wishlist/{wishlistItem}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
    Route::post('/apps/{slug}/reviews', [StorefrontController::class, 'storeReview'])
        ->middleware('throttle:public-form')
        ->name('products.reviews.store');
    Route::get('/account/purchases', [AccountController::class, 'purchases'])->name('account.purchases');
    Route::get('/account/downloads', [AccountController::class, 'downloads'])->name('account.downloads');
    Route::get('/account/invoices', [InvoiceController::class, 'index'])->name('account.invoices.index');
    Route::get('/account/invoices/{invoiceOrder}', [InvoiceController::class, 'show'])->name('account.invoices.show');
    Route::get('/account/settings', [AccountController::class, 'settings'])->name('account.settings');
    Route::patch('/account/settings', [AccountController::class, 'update'])->name('account.settings.update');
    Route::get('/account/billing', [BillingProfileController::class, 'edit'])->name('account.billing');
    Route::put('/account/billing', [BillingProfileController::class, 'update'])->name('account.billing.update');
    Route::get('/account/security', [SecuritySessionController::class, 'index'])->name('account.security');
    Route::post('/account/security/sessions/{session}/revoke', [SecuritySessionController::class, 'revoke'])
        ->middleware('throttle:session-revoke')
        ->name('account.security.sessions.revoke');
    Route::get('/account/support', [SupportTicketController::class, 'index'])->name('account.support.index');
    Route::post('/account/support/tickets', [SupportTicketController::class, 'store'])
        ->middleware('throttle:support-ticket')
        ->name('account.support.store');
    Route::get('/account/support/attachments/{attachment}', [SupportTicketController::class, 'downloadAttachment'])
        ->name('account.support.attachments.download');
    Route::post('/account/support/tickets/{supportTicket}/messages', [SupportTicketController::class, 'reply'])
        ->middleware('throttle:support-reply')
        ->name('account.support.reply');
    Route::get('/account/support/tickets/{supportTicket}', [SupportTicketController::class, 'show'])
        ->name('account.support.show');
    Route::get('/account/downloads/{downloadableAsset}/url', [DownloadController::class, 'url'])
        ->middleware('verified')
        ->name('downloads.url');
    Route::post('/account/credentials/{credential}/reveal', [CredentialController::class, 'reveal'])
        ->middleware(['verified', 'throttle:credential-reveal'])
        ->name('credentials.reveal');
    Route::get('/account/credentials/{credential}/license.txt', [CredentialController::class, 'downloadText'])
        ->middleware(['verified', 'throttle:credential-reveal'])
        ->name('credentials.license-text');
    Route::post('/account/credentials/offline-file', [OfflineLicenseController::class, 'generate'])
        ->middleware(['verified', 'throttle:credential-reveal'])
        ->name('credentials.offline-file');
    Route::get('/account/credentials/{credential}/offline-file', [OfflineLicenseController::class, 'download'])
        ->middleware(['verified', 'throttle:credential-reveal'])
        ->name('credentials.offline-file.direct');

    Route::middleware('verified')->group(function (): void {
        Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
        Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
        Route::get('/checkout/complete/{order}', [CheckoutController::class, 'complete'])->name('checkout.complete');
    });
});

Route::prefix('admin/support')
    ->name('staff.support.')
    ->middleware(['auth:staff', 'staff.admin'])
    ->controller(StaffSupportController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/attachments/{attachment}', 'downloadAttachment')->name('attachments.download');
        Route::patch('/attachments/{attachment}/review', 'reviewAttachment')->name('attachments.review');
        Route::get('/{supportTicket}', 'show')->name('show');
        Route::post('/{supportTicket}/messages', 'reply')
            ->middleware('throttle:support-reply')
            ->name('reply');
        Route::patch('/{supportTicket}', 'update')->name('update');
    });

Route::get('/downloads/{downloadableAsset}', [DownloadController::class, 'download'])
    ->middleware(['auth', 'verified', 'signed'])
    ->name('downloads.show');
