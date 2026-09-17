<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $reference
 * @property OrderStatus $status
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property string|null $phone
 * @property string $address_line
 * @property string $postal_code
 * @property string $city
 * @property string $country
 * @property int $subtotal_cents
 * @property int $shipping_cents
 * @property int $total_cents
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property int|null $cancelled_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderLine> $lines
 * @property-read User|null $canceller
 */
#[Fillable([
    'reference', 'status', 'email', 'first_name', 'last_name', 'phone',
    'address_line', 'postal_code', 'city', 'country',
    'subtotal_cents', 'shipping_cents', 'total_cents',
    'cancelled_at', 'cancelled_by',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /** @return HasMany<OrderLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    /** @return BelongsTo<User, $this> */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /** @return HasMany<StockMovement, $this> */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::Cancelled;
    }

    public function customerName(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function withStatus(Builder $query, OrderStatus $status): void
    {
        $query->where('orders.status', $status);
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function latestFirst(Builder $query): void
    {
        $query->orderByDesc('orders.created_at')->orderByDesc('orders.id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal_cents' => 'integer',
            'shipping_cents' => 'integer',
            'total_cents' => 'integer',
            'cancelled_at' => 'datetime',
        ];
    }
}
