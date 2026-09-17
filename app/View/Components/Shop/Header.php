<?php

declare(strict_types=1);

namespace App\View\Components\Shop;

use App\Cart\CartSummarizer;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Header extends Component
{
    public function __construct(private readonly CartSummarizer $cart) {}

    public function render(): View
    {
        return view('components.shop.header', [
            'categories' => Category::query()->orderBy('position')->get(['id', 'name', 'slug']),
            'cartCount' => $this->cart->summarize()->count(),
        ]);
    }
}
