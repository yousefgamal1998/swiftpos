<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstaPayController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MarketplaceCardController;
use App\Http\Controllers\MarketplaceCategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\POSController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Admin\CardController as AdminCardController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\InstaPayController as AdminInstaPayController;
use App\Http\Controllers\Admin\MarketplaceController as AdminMarketplaceController;
use App\Http\Controllers\Admin\StoreController as AdminStoreController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('home');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth'])
    ->name('dashboard');

Route::get('/instapay/{order}', [InstaPayController::class, 'show'])->name('instapay.show');
Route::post('/instapay/{order}/confirm', [InstaPayController::class, 'confirmSent'])->name('instapay.confirm');
Route::post('/instapay/{order}/screenshot', [InstaPayController::class, 'uploadScreenshot'])->name('instapay.screenshot');

Route::middleware('auth')->group(function () {
    Route::post('/instapay/initiate', [InstaPayController::class, 'initiate'])->name('instapay.initiate');

    Route::get('/restaurants', [RestaurantController::class, 'index'])->name('restaurants.index');
    Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
    Route::post('/cart/items', [CartController::class, 'storeItem'])->name('cart.items.store');
    Route::patch('/cart/items/{cartItem}', [CartController::class, 'updateItem'])->name('cart.items.update');
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroyItem'])->name('cart.items.destroy');
    Route::post('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');

    Route::get('/cards/{slug}/product', [MarketplaceCardController::class, 'products'])->name('cards.products');
    Route::get('/cards/{slug}', [MarketplaceCardController::class, 'show'])->name('cards.show');
    Route::get('/categories/{category:slug}', [MarketplaceCategoryController::class, 'show'])->name('categories.show');

    Route::middleware('role:admin|manager')->group(function () {
        Route::resource('products', ProductController::class)->except(['show']);
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/{product}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::resource('categories', AdminCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::resource('stores', AdminStoreController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::resource('products', AdminProductController::class)->only(['index', 'store', 'update', 'destroy']);

            Route::get('/marketplace', [AdminMarketplaceController::class, 'index'])->name('marketplace.index');
            Route::post('/marketplace/categories', [AdminMarketplaceController::class, 'storeCategory'])
                ->name('marketplace.categories.store');
            Route::put('/marketplace/categories/{category}', [AdminMarketplaceController::class, 'updateCategory'])
                ->name('marketplace.categories.update')
                ->whereNumber('category');
            Route::delete('/marketplace/categories/{category}', [AdminMarketplaceController::class, 'destroyCategory'])
                ->name('marketplace.categories.destroy')
                ->whereNumber('category');
            Route::post('/marketplace/cards', [AdminMarketplaceController::class, 'storeCard'])->name('marketplace.cards.store');
            Route::put('/marketplace/cards/{card}', [AdminMarketplaceController::class, 'updateCard'])
                ->name('marketplace.cards.update')
                ->whereNumber('card');
            Route::delete('/marketplace/cards/{card}', [AdminMarketplaceController::class, 'destroyCard'])
                ->name('marketplace.cards.destroy')
                ->whereNumber('card');
            Route::put('/marketplace/cards/reorder', [AdminMarketplaceController::class, 'reorderCards'])
                ->name('marketplace.cards.reorder');

            Route::get('/cards', [AdminCardController::class, 'index'])->name('cards.index');
            Route::get('/cards/create', [AdminCardController::class, 'create'])->name('cards.create');
            Route::post('/cards', [AdminCardController::class, 'store'])->name('cards.store');
            Route::put('/cards/reorder', [AdminCardController::class, 'reorder'])->name('cards.reorder');
            Route::get('/cards/{card}/edit', [AdminCardController::class, 'edit'])->name('cards.edit')->whereNumber('card');
            Route::put('/cards/{card}', [AdminCardController::class, 'update'])->name('cards.update')->whereNumber('card');
            Route::delete('/cards/{card}', [AdminCardController::class, 'destroy'])->name('cards.destroy')->whereNumber('card');
            Route::patch('/cards/{card}/remove-image', [AdminCardController::class, 'removeImage'])->name('cards.remove-image')->whereNumber('card');
            Route::patch('/cards/{card}/toggle', [AdminCardController::class, 'toggle'])->name('cards.toggle')->whereNumber('card');

            Route::get('/instapay', [AdminInstaPayController::class, 'index'])->name('instapay.index');
            Route::post('/instapay/{order}/approve', [AdminInstaPayController::class, 'approve'])->name('instapay.approve');
            Route::post('/instapay/{order}/reject', [AdminInstaPayController::class, 'reject'])->name('instapay.reject');
        });
    });

    Route::middleware('role:admin|manager|cashier')->group(function () {
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');

        Route::get('/pos', [POSController::class, 'index'])->name('pos.index');
        Route::post('/pos/sessions/open', [POSController::class, 'openSession'])->name('pos.sessions.open');
        Route::post('/pos/sessions/{session}/close', [POSController::class, 'closeSession'])->name('pos.sessions.close');
        Route::post('/pos/checkout', [POSController::class, 'checkout'])->name('pos.checkout');
    });
});

require __DIR__.'/auth.php';
