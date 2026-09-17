<?php

declare(strict_types=1);

use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\PlaceOrder;
use App\Actions\Stock\AdjustStock;
use App\Models\ArticleVariant;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/*
 * Deterministic, single-process: while an action is inside its transaction
 * (observed through DB::listen, right after it ran a given query), a second
 * PostgreSQL session tries to lock the same rows with a short lock_timeout.
 * No sleeps, no timing assumptions: a held lock always yields SQLSTATE 55P03.
 *
 * The probe must run BEFORE the action's first write to a row: an UPDATE
 * locks the row by itself, so probing after it would pass even without any
 * FOR UPDATE — each test below was checked by removing the lock it targets.
 */

beforeEach(fn () => Queue::fake());

/**
 * Runs $probe once, from inside the action's transaction, right after the
 * first query containing $marker. Returns what $probe returned.
 */
function probeWhile(string $marker, Closure $probe, Closure $action): mixed
{
    $result = null;
    $done = false;

    DB::listen(function (QueryExecuted $query) use ($marker, $probe, &$result, &$done) {
        if (! $done && $query->connectionName === 'pgsql' && str_contains($query->sql, $marker)) {
            $done = true;
            $result = $probe();
        }
    });

    $action();

    expect($done)->toBeTrue("No query matched [{$marker}]: the probe never ran.");

    return $result;
}

it('sees the rows as free when no transaction holds them', function () {
    [$product, $variant, $marking] = sellable();

    expect($this->canLock('article_variants', $variant->id))->toBeTrue()
        ->and($this->canLock('markings', $marking->id))->toBeTrue()
        ->and($this->canLock('products', $product->id))->toBeTrue();
});

it('holds FOR UPDATE on the variant and the marking while placing an order', function () {
    [$product, $variant, $marking] = sellable();
    $unrelated = ArticleVariant::factory()->for($variant->article)->create();

    $locks = probeWhile(
        marker: 'insert into "orders"',
        probe: fn () => [
            'variant' => $this->canLock('article_variants', $variant->id),
            'marking' => $this->canLock('markings', $marking->id),
            'unrelated variant' => $this->canLock('article_variants', $unrelated->id),
            'product, shared' => $this->canLock('products', $product->id, 'FOR SHARE'),
            'product, exclusive' => $this->canLock('products', $product->id, 'FOR UPDATE'),
        ],
        action: fn () => app(PlaceOrder::class)->handle([line($product, $variant)], customer()),
    );

    expect($locks)->toBe([
        'variant' => false,              // another checkout would wait here
        'marking' => false,
        'unrelated variant' => true,     // row-level: other sizes stay sellable
        'product, shared' => true,       // other checkouts of this product are not blocked
        'product, exclusive' => false,   // but an admin cannot reprice it mid-checkout
    ]);
});

it('already holds the locks when it reads the stock it checks', function () {
    // The locking SELECT *is* the read: there is no window between reading
    // the stock and locking it.
    [$product, $variant] = sellable();

    $locked = probeWhile(
        marker: 'from "markings" where',
        probe: fn () => $this->canLock('article_variants', $variant->id),
        action: fn () => app(PlaceOrder::class)->handle([line($product, $variant)], customer()),
    );

    expect($locked)->toBeFalse();
});

it('releases every lock once the order is committed', function () {
    [$product, $variant, $marking] = sellable();

    app(PlaceOrder::class)->handle([line($product, $variant)], customer());

    expect($this->canLock('article_variants', $variant->id))->toBeTrue()
        ->and($this->canLock('markings', $marking->id))->toBeTrue();
});

it('hides an uncommitted decrement from plain reads, which is why stock is only read with a lock', function () {
    // Documents the PostgreSQL behaviour the design relies on. While order A
    // is between its UPDATE and its COMMIT, a plain SELECT from B still sees
    // stock = 1 (MVCC) and would happily sell it again. A locking read waits.
    // PlaceOrder never reads stock without FOR UPDATE (see the previous test).
    [$product, $variant] = sellable(variantStock: 1);

    $seen = probeWhile(
        marker: 'update "article_variants"',
        probe: fn () => [
            'plain read' => $this->probe()->table('article_variants')->where('id', $variant->id)->value('stock'),
            'can lock' => $this->canLock('article_variants', $variant->id),
        ],
        action: fn () => app(PlaceOrder::class)->handle([line($product, $variant)], customer()),
    );

    expect($seen)->toBe(['plain read' => 1, 'can lock' => false])
        ->and($variant->fresh()->stock)->toBe(0);
});

it('locks the order and its components while cancelling', function () {
    [$product, $variant, $marking] = sellable();
    $order = app(PlaceOrder::class)->handle([line($product, $variant)], customer());

    $locks = probeWhile(
        marker: 'from "markings" where',   // components locked, nothing written yet
        probe: fn () => [
            'order' => $this->canLock('orders', $order->id),
            'variant' => $this->canLock('article_variants', $variant->id),
            'marking' => $this->canLock('markings', $marking->id),
        ],
        action: fn () => app(CancelOrder::class)->handle($order, User::factory()->admin()->create()),
    );

    expect($locks)->toBe(['order' => false, 'variant' => false, 'marking' => false]);
});

it('locks the row while adjusting stock by hand', function () {
    $variant = ArticleVariant::factory()->stock(5)->create();
    $user = User::factory()->create();

    $locked = probeWhile(
        marker: 'from "article_variants" where',   // the locking read, before the UPDATE
        probe: fn () => $this->canLock('article_variants', $variant->id),
        action: fn () => app(AdjustStock::class)->handle($variant, 3, $user),
    );

    expect($locked)->toBeFalse();
});
