<?php

declare(strict_types=1);

use App\Models\ArticleVariant;
use App\Models\Marking;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
 * These rules are enforced by PostgreSQL itself. The tests write with raw
 * queries or plain Eloquent, bypassing every application-level check.
 */

/** Runs $write in a savepoint so the surrounding test transaction survives the error. */
function rejectedByDatabase(Closure $write): string
{
    try {
        DB::transaction($write);
    } catch (QueryException $e) {
        return $e->getMessage();
    }

    throw new RuntimeException('The database accepted the write.');
}

function lineRow(Order $order, Product $product, ArticleVariant $variant, array $overrides = []): array
{
    return [
        'order_id' => $order->id,
        'product_id' => $product->id,
        'article_id' => $product->article_id,
        'article_variant_id' => $variant->id,
        'marking_id' => $product->marking_id,
        'product_name' => 'x',
        'variant_label' => 'x',
        'unit_price_cents' => 1000,
        'quantity' => 1,
        'line_total_cents' => 1000,
        'marking_units' => 1,
        ...$overrides,
    ];
}

it('rejects a negative variant stock', function () {
    $variant = ArticleVariant::factory()->stock(1)->create();

    expect(rejectedByDatabase(fn () => $variant->decrement('stock', 2)))
        ->toContain('article_variants_stock_check');
});

it('rejects a negative marking stock', function () {
    $marking = Marking::factory()->stock(0)->create();

    expect(rejectedByDatabase(fn () => $marking->decrement('stock')))
        ->toContain('markings_stock_check');
});

it('rejects an order line whose variant belongs to another article', function () {
    [$tshirt] = sellable();
    [, $hoodieVariant] = sellable();
    $order = Order::factory()->create();

    // Even lying about article_id cannot help: one of the two composite keys fails.
    expect(rejectedByDatabase(fn () => DB::table('order_lines')->insert(lineRow($order, $tshirt, $hoodieVariant))))
        ->toContain('order_lines_article_variant_id_article_id_foreign');

    expect(rejectedByDatabase(fn () => DB::table('order_lines')->insert(
        lineRow($order, $tshirt, $hoodieVariant, ['article_id' => $hoodieVariant->article_id])
    )))->toContain('order_lines_product_id_article_id_foreign');
});

it('rejects an order line whose marking is not the product marking', function () {
    [$product, $variant] = sellable();
    $otherMarking = Marking::factory()->create();
    $order = Order::factory()->create();

    expect(rejectedByDatabase(fn () => DB::table('order_lines')->insert(
        lineRow($order, $product, $variant, ['marking_id' => $otherMarking->id])
    )))->toContain('order_lines_product_id_marking_id_foreign');
});

it('accepts a consistent order line', function () {
    [$product, $variant] = sellable();
    $order = Order::factory()->create();

    DB::table('order_lines')->insert(lineRow($order, $product, $variant));

    expect(DB::table('order_lines')->count())->toBe(1);
});

it('rejects an order whose total does not add up', function () {
    expect(rejectedByDatabase(fn () => Order::factory()->create([
        'subtotal_cents' => 1000, 'shipping_cents' => 500, 'total_cents' => 1400,
    ])))->toContain('orders_amounts_check');
});

it('rejects a line total that is not unit price times quantity', function () {
    [$product, $variant] = sellable();
    $order = Order::factory()->create();

    expect(rejectedByDatabase(fn () => DB::table('order_lines')->insert(
        lineRow($order, $product, $variant, ['quantity' => 2])
    )))->toContain('order_lines_total_check');
});

it('rejects the same article and marking pair twice', function () {
    [$product] = sellable();

    expect(rejectedByDatabase(fn () => Product::factory()->create([
        'article_id' => $product->article_id,
        'marking_id' => $product->marking_id,
    ])))->toContain('products_article_id_marking_id_unique');
});

it('refuses to delete a product that was sold', function () {
    [$product, $variant] = sellable();
    DB::table('order_lines')->insert(lineRow(Order::factory()->create(), $product, $variant));

    expect(rejectedByDatabase(fn () => $product->delete()))->toContain('foreign');
});
