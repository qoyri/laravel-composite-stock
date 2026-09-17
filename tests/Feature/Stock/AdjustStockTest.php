<?php

declare(strict_types=1);

use App\Actions\Stock\AdjustStock;
use App\Enums\StockMovementReason;
use App\Models\ArticleVariant;
use App\Models\Marking;
use App\Models\StockMovement;
use App\Models\User;
use App\Stock\InvalidStockAdjustment;

it('applies a relative adjustment and logs it', function (string $type) {
    $stockable = $type === 'variant'
        ? ArticleVariant::factory()->stock(10)->create()
        : Marking::factory()->stock(10)->create();
    $user = User::factory()->staff()->create();

    app(AdjustStock::class)->handle($stockable, 5, $user, 'Réception fournisseur');

    expect($stockable->fresh()->stock)->toBe(15);
    $movement = StockMovement::sole();
    expect($movement->stockable->is($stockable))->toBeTrue()
        ->and($movement->delta)->toBe(5)
        ->and($movement->stock_after)->toBe(15)
        ->and($movement->reason)->toBe(StockMovementReason::Adjustment)
        ->and($movement->user_id)->toBe($user->id)
        ->and($movement->note)->toBe('Réception fournisseur');
})->with(['variant', 'marking']);

it('applies the delta to the current stock, not to the value the user saw', function () {
    // The user opened the page at 10 and adds 5 received items. Meanwhile a
    // sale took the stock to 9. Result must be 14, not the 15 an absolute
    // "set stock to 15" form would write.
    $variant = ArticleVariant::factory()->stock(10)->create();
    $staleCopy = $variant->replicate();
    $staleCopy->id = $variant->id;
    $staleCopy->exists = true;

    $variant->update(['stock' => 9]);
    app(AdjustStock::class)->handle($staleCopy, 5, User::factory()->create());

    expect($variant->fresh()->stock)->toBe(14);
});

it('refuses to go below zero and writes nothing', function () {
    $variant = ArticleVariant::factory()->stock(3)->create();

    expect(fn () => app(AdjustStock::class)->handle($variant, -4, User::factory()->create()))
        ->toThrow(InvalidStockAdjustment::class);

    expect($variant->fresh()->stock)->toBe(3)
        ->and(StockMovement::count())->toBe(0);
});

it('refuses a zero adjustment', function () {
    app(AdjustStock::class)->handle(ArticleVariant::factory()->create(), 0, User::factory()->create());
})->throws(InvalidStockAdjustment::class);

it('refuses to adjust an unlimited marking', function () {
    app(AdjustStock::class)->handle(Marking::factory()->unlimited()->create(), 5, User::factory()->create());
})->throws(InvalidStockAdjustment::class);
