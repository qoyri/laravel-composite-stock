<?php

declare(strict_types=1);

namespace App\View\Components\Shop;

use App\Cart\CartSummarizer;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CartDrawer extends Component
{
    public function __construct(private readonly CartSummarizer $cart) {}

    public function render(): View
    {
        return view('components.shop.cart-drawer', [
            'summary' => $this->cart->summarize(),
        ]);
    }
}
