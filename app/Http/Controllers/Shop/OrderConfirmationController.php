<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;

class OrderConfirmationController extends Controller
{
    public function __invoke(Order $order): View
    {
        return view('shop.confirmation', ['order' => $order->load('lines')]);
    }
}
