<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Orders\CancelOrder;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Order::class);

        $status = $request->enum('statut', OrderStatus::class);

        return view('admin.orders.index', [
            'orders' => Order::query()
                ->when($status, fn ($q, OrderStatus $s) => $q->withStatus($s))
                ->withCount('lines')
                ->withSum('lines', 'quantity')
                ->latestFirst()
                ->paginate(20)
                ->withQueryString(),
            'status' => $status,
            'statuses' => OrderStatus::cases(),
        ]);
    }

    public function show(Order $order): View
    {
        Gate::authorize('view', $order);

        return view('admin.orders.show', [
            'order' => $order->load(['lines.variant', 'lines.marking', 'canceller']),
            'movements' => $order->stockMovements()->with('stockable')->orderBy('id')->get(),
        ]);
    }

    public function cancel(Request $request, Order $order, CancelOrder $cancelOrder): RedirectResponse
    {
        Gate::authorize('cancel', $order);

        if ($order->isCancelled()) {
            return back()->with('status', 'Cette commande était déjà annulée.');
        }

        /** @var User $user */
        $user = $request->user();
        $cancelOrder->handle($order, $user);

        return back()->with('status', 'Commande annulée, stock remis en place.');
    }
}
