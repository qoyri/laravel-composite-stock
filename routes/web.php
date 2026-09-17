<?php

declare(strict_types=1);

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Shop;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront — no customer accounts, the cart lives in the session
|--------------------------------------------------------------------------
*/

Route::get('/', Shop\HomeController::class)->name('home');
Route::get('/produits', Shop\CatalogController::class)->name('catalog');
Route::get('/produits/{sellableProduct}', Shop\ProductController::class)->name('products.show');
Route::get('/personnaliser', Shop\MarkingController::class)->name('markings');
Route::view('/le-magasin', 'shop.about')->name('about');

Route::controller(Shop\CartController::class)->prefix('panier')->name('cart.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/lignes', 'store')->name('store');
    Route::patch('/lignes/{line}', 'update')->name('update')->where('line', '[0-9]+:[0-9]+');
    Route::delete('/lignes/{line}', 'destroy')->name('destroy')->where('line', '[0-9]+:[0-9]+');
    Route::delete('/', 'clear')->name('clear');
});

Route::get('/commande', [Shop\CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/commande', [Shop\CheckoutController::class, 'store'])
    ->middleware('throttle:checkout')
    ->name('checkout.store');
Route::get('/commande/{order}/confirmation', Shop\OrderConfirmationController::class)
    ->middleware('signed')
    ->name('orders.confirmation');

/*
|--------------------------------------------------------------------------
| Back-office — authenticated; what each role may do is in app/Policies
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/connexion', [LoginController::class, 'create'])->name('login');
        Route::post('/connexion', [LoginController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/deconnexion', [LoginController::class, 'destroy'])->name('logout');

        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        Route::resource('articles', Admin\ArticleController::class)->except('show');
        Route::post('/articles/{article}/variantes', [Admin\ArticleVariantController::class, 'store'])->name('articles.variants.store');
        Route::delete('/articles/{article}/variantes/{variant}', [Admin\ArticleVariantController::class, 'destroy'])
            ->scopeBindings()
            ->name('articles.variants.destroy');

        Route::resource('marquages', Admin\MarkingController::class)
            ->parameters(['marquages' => 'marking'])
            ->names('markings')
            ->except(['show', 'destroy']);

        Route::resource('produits', Admin\ProductController::class)
            ->parameters(['produits' => 'product'])
            ->names('products')
            ->except(['show', 'destroy']);

        Route::get('/stock', [Admin\StockAdjustmentController::class, 'index'])->name('stock.index');
        Route::post('/stock/variantes/{variant}', [Admin\StockAdjustmentController::class, 'adjustVariant'])->name('stock.variants.adjust');
        Route::post('/stock/marquages/{marking}', [Admin\StockAdjustmentController::class, 'adjustMarking'])->name('stock.markings.adjust');

        Route::get('/commandes', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('/commandes/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::post('/commandes/{order}/annulation', [Admin\OrderController::class, 'cancel'])->name('orders.cancel');
    });
});
