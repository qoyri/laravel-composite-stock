<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\ArticleVariant;
use App\Models\Marking;
use App\Models\Order;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    private const LOW_STOCK = 3;

    public function __invoke(): View
    {
        $confirmed = Order::query()->withStatus(OrderStatus::Confirmed);

        return view('admin.dashboard', [
            'stats' => [
                'revenue30' => (int) (clone $confirmed)->where('created_at', '>=', now()->subDays(30))->sum('total_cents'),
                'orders30' => (clone $confirmed)->where('created_at', '>=', now()->subDays(30))->count(),
                'soldOutVariants' => ArticleVariant::where('stock', 0)->count(),
                'lowMarkings' => Marking::active()->where('is_unlimited', false)->where('stock', '<=', 10)->count(),
            ],
            'recentOrders' => Order::query()->latestFirst()->withCount('lines')->limit(8)->get(),
            'lowVariants' => ArticleVariant::query()
                ->with('article')
                ->where('stock', '<=', self::LOW_STOCK)
                ->orderBy('stock')->orderBy('id')
                ->limit(8)
                ->get(),
            'lowMarkings' => Marking::query()->active()->where('is_unlimited', false)
                ->orderBy('stock')->limit(5)->get(),
        ]);
    }
}
