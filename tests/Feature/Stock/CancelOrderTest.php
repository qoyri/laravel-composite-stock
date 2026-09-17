<?php

declare(strict_types=1);

use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\PlaceOrder;
use App\Enums\OrderStatus;
use App\Enums\StockMovementReason;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

it('gives both components back and records who cancelled', function () {
    [$product, $variant, $marking] = sellable(variantStock: 10, markingStock: 10);
    $order = app(PlaceOrder::class)->handle([line($product, $variant, 3)], customer());
    $admin = User::factory()->admin()->create();

    $this->freezeSecond();
    app(CancelOrder::class)->handle($order, $admin);

    $order->refresh();
    expect($variant->fresh()->stock)->toBe(10)
        ->and($marking->fresh()->stock)->toBe(10)
        ->and($order->status)->toBe(OrderStatus::Cancelled)
        ->and($order->cancelled_at?->equalTo(now()))->toBeTrue()
        ->and($order->cancelled_by)->toBe($admin->id);

    $movements = StockMovement::where('order_id', $order->id)
        ->where('reason', StockMovementReason::Cancellation)->get();
    expect($movements->pluck('delta')->all())->toEqualCanonicalizing([3, 3])
        ->and($movements->every(fn ($m) => $m->user_id === $admin->id))->toBeTrue();
});

it('restocks the marking units consumed at sale time, even if units_per_item changed since', function () {
    [$product, $variant, $marking] = sellable(variantStock: 10, markingStock: 10, unitsPerItem: 2);
    $order = app(PlaceOrder::class)->handle([line($product, $variant, 2)], customer());
    expect($marking->fresh()->stock)->toBe(6);

    $product->update(['units_per_item' => 3]);
    app(CancelOrder::class)->handle($order, User::factory()->admin()->create());

    expect($marking->fresh()->stock)->toBe(10);
});

it('does not touch an unlimited marking', function () {
    [$product, $variant, $marking] = sellable(variantStock: 5, unlimited: true);
    $order = app(PlaceOrder::class)->handle([line($product, $variant, 2)], customer());

    app(CancelOrder::class)->handle($order, User::factory()->admin()->create());

    expect($variant->fresh()->stock)->toBe(5)
        ->and($marking->fresh()->stock)->toBe(0)
        ->and(StockMovement::where('stockable_type', 'marking')->exists())->toBeFalse();
});

it('is idempotent: cancelling twice restocks once', function () {
    [$product, $variant] = sellable(variantStock: 10);
    $order = app(PlaceOrder::class)->handle([line($product, $variant, 4)], customer());
    $admin = User::factory()->admin()->create();

    app(CancelOrder::class)->handle($order, $admin);
    // A stale copy of the order, as a second browser tab would hold it.
    app(CancelOrder::class)->handle($order, $admin);

    expect($variant->fresh()->stock)->toBe(10)
        ->and(StockMovement::where('reason', StockMovementReason::Cancellation)->count())->toBe(2);
});

it('aggregates restocking of several lines on the same components', function () {
    [$product, $variant, $marking] = sellable(variantStock: 10, markingStock: 10);
    $order = app(PlaceOrder::class)->handle([line($product, $variant, 1)], customer());
    $order2 = app(PlaceOrder::class)->handle([line($product, $variant, 2)], customer());

    app(CancelOrder::class)->handle($order2, User::factory()->admin()->create());

    expect($variant->fresh()->stock)->toBe(9)
        ->and($marking->fresh()->stock)->toBe(9);
});

it('does not invent marking stock when an unlimited marking became finite after the sale', function () {
    [$product, $variant, $marking] = sellable(variantStock: 5, unlimited: true);
    $order = app(PlaceOrder::class)->handle([line($product, $variant, 2)], customer());
    $marking->update(['is_unlimited' => false, 'stock' => 7]);

    app(CancelOrder::class)->handle($order, User::factory()->admin()->create());

    expect($marking->fresh()->stock)->toBe(7);
});
