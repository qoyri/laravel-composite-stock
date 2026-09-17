<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Order $order): bool
    {
        return true;
    }

    /**
     * Cancelling refunds the customer and restocks: admins only.
     * An already-cancelled order is not a permission problem: CancelOrder
     * is idempotent and the controller reports it.
     */
    public function cancel(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }
}
