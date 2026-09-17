<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Cart\Cart;
use App\Cart\CartSummarizer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\AddToCartRequest;
use App\Http\Requests\Shop\UpdateCartLineRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CartController extends Controller
{
    public function __construct(private readonly Cart $cart) {}

    public function index(CartSummarizer $summarizer): View
    {
        return view('shop.cart', ['summary' => $summarizer->summarize()]);
    }

    public function store(AddToCartRequest $request): RedirectResponse
    {
        $this->cart->add($request->line());

        // Opens the cart drawer on the next page, as the original shop did.
        return back()->with('cart.opened', true);
    }

    public function update(UpdateCartLineRequest $request, string $line): RedirectResponse
    {
        $this->cart->update($line, $request->integer('quantity'));

        return back();
    }

    public function destroy(string $line): RedirectResponse
    {
        $this->cart->remove($line);

        return back();
    }

    public function clear(): RedirectResponse
    {
        $this->cart->clear();

        return to_route('cart.index');
    }
}
