<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\ArticleVariant;
use App\Models\Marking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Outside production, lazy loading throws: an N+1 fails the test suite
        // instead of silently slowing down a page.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Store short aliases instead of PHP class names in stock_movements.
        Relation::enforceMorphMap([
            'article_variant' => ArticleVariant::class,
            'marking' => Marking::class,
        ]);
    }
}
