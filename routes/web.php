<?php

declare(strict_types=1);

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
