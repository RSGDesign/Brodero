<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public pages
Route::get('/', function () { 
    $products = Product::where('is_published', true)->orderBy('created_at', 'desc')->limit(6)->get();
    return view('home', compact('products')); 
})->name('home');
Route::get('/about', function () { return view('about'); })->name('about');
Route::get('/contact', function () { return view('contact'); })->name('contact');

// Public shop
Route::get('/shop', [ProductController::class, 'index'])->name('shop.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('shop.show');

// Cart (authenticated)
Route::middleware('auth')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'addItem'])->name('cart.add');
    Route::patch('/cart/items/{item}', [CartController::class, 'updateItem'])->name('cart.update');
    Route::delete('/cart/items/{item}', [CartController::class, 'removeItem'])->name('cart.remove');
    Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
    Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
    Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');
});

// Checkout (authenticated)
Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'process'])->name('checkout.process');
    Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
});

// Stripe webhook (no CSRF)
Route::post('/webhook/stripe', [CheckoutController::class, 'webhook'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// API routes
Route::post('/api/subscribe', [\App\Http\Controllers\NewsletterController::class, 'subscribe']);

// Dashboard (admin only)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'admin'])->name('dashboard');

// Account (regular users)
Route::get('/account', function () {
    return view('account');
})->middleware(['auth', 'verified'])->name('account');

// Profile
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin panel
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');

    // API endpoints for admin
    Route::get('/api/stats', [App\Http\Controllers\Admin\DashboardController::class, 'stats'])->name('api.stats');
    
    // Pages API
    Route::get('/api/pages', [App\Http\Controllers\Admin\PageController::class, 'index']);
    Route::get('/api/pages/{id}', [App\Http\Controllers\Admin\PageController::class, 'show']);
    Route::post('/api/pages', [App\Http\Controllers\Admin\PageController::class, 'store']);
    Route::put('/api/pages/{id}', [App\Http\Controllers\Admin\PageController::class, 'update']);
    Route::delete('/api/pages/{id}', [App\Http\Controllers\Admin\PageController::class, 'destroy']);

    // Products API
    Route::get('/api/products', [App\Http\Controllers\Admin\ProductController::class, 'apiIndex']);
    Route::delete('/api/products/{id}', [App\Http\Controllers\Admin\ProductController::class, 'destroy']);
    
    // Product Files API
    Route::get('/products/{product}/files', [App\Http\Controllers\Admin\ProductFileController::class, 'index']);
    Route::post('/products/{product}/files', [App\Http\Controllers\Admin\ProductFileController::class, 'upload']);
    Route::delete('/products/{product}/files/{file}', [App\Http\Controllers\Admin\ProductFileController::class, 'destroy']);
    
    // Categories API
    Route::get('/api/categories', [App\Http\Controllers\Admin\CategoryController::class, 'index']);
    Route::get('/api/categories/{id}', [App\Http\Controllers\Admin\CategoryController::class, 'show']);
    Route::post('/api/categories', [App\Http\Controllers\Admin\CategoryController::class, 'store']);
    Route::put('/api/categories/{id}', [App\Http\Controllers\Admin\CategoryController::class, 'update']);
    Route::delete('/api/categories/{id}', [App\Http\Controllers\Admin\CategoryController::class, 'destroy']);
    
    // Media API
    Route::get('/api/media', [App\Http\Controllers\Admin\MediaController::class, 'index']);
    Route::post('/api/media/upload', [App\Http\Controllers\Admin\MediaController::class, 'upload']);
    Route::delete('/api/media/{id}', [App\Http\Controllers\Admin\MediaController::class, 'destroy']);
    
    // Coupons API
    Route::get('/api/coupons', [App\Http\Controllers\Admin\CouponController::class, 'index']);
    Route::get('/api/coupons/{id}', [App\Http\Controllers\Admin\CouponController::class, 'show']);
    Route::post('/api/coupons', [App\Http\Controllers\Admin\CouponController::class, 'store']);
    Route::put('/api/coupons/{id}', [App\Http\Controllers\Admin\CouponController::class, 'update']);
    Route::delete('/api/coupons/{id}', [App\Http\Controllers\Admin\CouponController::class, 'destroy']);
    
    // Customers API
    Route::get('/api/customers', [App\Http\Controllers\Admin\CustomerController::class, 'index']);
    
    // Orders API
    Route::get('/api/orders', [App\Http\Controllers\Admin\OrderController::class, 'apiIndex']);
    Route::put('/api/orders/{id}/status', [App\Http\Controllers\Admin\OrderController::class, 'updateStatus']);
    
    // Newsletter API
    Route::get('/api/newsletter', [\App\Http\Controllers\NewsletterController::class, 'index']);
    
    // Settings API
    Route::get('/api/settings', [App\Http\Controllers\Admin\SettingsController::class, 'index']);
    Route::post('/api/settings', [App\Http\Controllers\Admin\SettingsController::class, 'update']);

    // Resource routes for views (products, categories, coupons)
    Route::resource('products', \App\Http\Controllers\Admin\ProductController::class);
    Route::resource('categories', \App\Http\Controllers\Admin\CategoryController::class)->except(['index', 'show']);
    Route::resource('coupons', \App\Http\Controllers\Admin\CouponController::class);
    
    Route::get('orders', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])->name('orders.show');
});

require __DIR__.'/auth.php';
