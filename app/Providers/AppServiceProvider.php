<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\ArticleVariant;
use App\Models\Marking;
use App\Models\Product;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Outside production, lazy loading throws: an N+1 fails the test suite
        // instead of silently slowing down a page.
        Model::shouldBeStrict(! $this->app->isProduction());

        // migrate:fresh and db:wipe refuse to run in production.
        DB::prohibitDestructiveCommands($this->app->isProduction());

        // Store short aliases instead of PHP class names in stock_movements.
        Relation::enforceMorphMap([
            'article_variant' => ArticleVariant::class,
            'marking' => Marking::class,
        ]);

        // Storefront product URLs only resolve to products that can be sold.
        Route::bind('sellableProduct', fn (string $slug) => Product::query()
            ->sellable()
            ->where('slug', $slug)
            ->firstOrFail());

        // Each checkout takes row locks: keep a single client from hammering it.
        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(10)->by($request->session()->getId()));
    }
}
