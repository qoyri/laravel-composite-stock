<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\StockMovementReason;
use App\Models\ArticleVariant;
use App\Models\Marking;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Cancels a confirmed order and returns its components to stock.
 *
 * The order row is locked first: two admins clicking "cancel" at the same
 * time restock once. Components are then locked in the same order as
 * PlaceOrder (variants, then markings, by id) to avoid deadlocks.
 * Quantities come from the order lines, frozen at sale time.
 */
final class CancelOrder
{
    public function handle(Order $order, User $by): Order
    {
        return DB::transaction(function () use ($order, $by): Order {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === OrderStatus::Cancelled) {
                return $locked;
            }

            $lines = $locked->lines()->get();

            /** @var array<int, int> $variantQuantities */
            $variantQuantities = $lines->groupBy('article_variant_id')
                ->map(fn ($group) => (int) $group->sum(fn (OrderLine $l) => $l->quantity))
                ->all();
            /** @var array<int, int> $markingUnits */
            $markingUnits = $lines->groupBy('marking_id')
                ->map(fn ($group) => (int) $group->sum(fn (OrderLine $l) => $l->marking_units))
                ->filter(fn (int $units) => $units > 0)
                ->all();

            $variants = ArticleVariant::query()->whereKey(array_keys($variantQuantities))
                ->orderBy('id')->lockForUpdate()->get();
            $markings = Marking::query()->whereKey(array_keys($markingUnits))
                ->orderBy('id')->lockForUpdate()->get();

            foreach ($variants as $variant) {
                $this->restock($variant, $variantQuantities[$variant->id], $locked, $by);
            }

            // Lines sold with an unlimited marking recorded 0 units and were
            // filtered out above; the others are given back even if the marking
            // became unlimited since — the physical consumables still exist.
            foreach ($markings as $marking) {
                $this->restock($marking, $markingUnits[$marking->id], $locked, $by);
            }

            $locked->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $by->id,
            ]);

            return $locked;
        });
    }

    private function restock(ArticleVariant|Marking $stockable, int $quantity, Order $order, User $by): void
    {
        $stockable->increment('stock', $quantity);
        $stockable->stockMovements()->create([
            'delta' => $quantity,
            'stock_after' => $stockable->stock,
            'reason' => StockMovementReason::Cancellation,
            'order_id' => $order->id,
            'user_id' => $by->id,
        ]);
    }
}
