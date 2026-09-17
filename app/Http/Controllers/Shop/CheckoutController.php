<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Actions\Orders\PlaceOrder;
use App\Cart\Cart;
use App\Cart\CartSummarizer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\PlaceOrderRequest;
use App\Stock\InsufficientStock;
use App\Stock\Shortage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;

class CheckoutController extends Controller
{
    public function __construct(private readonly Cart $cart) {}

    public function create(CartSummarizer $summarizer): View|RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return to_route('cart.index');
        }

        return view('shop.checkout', ['summary' => $summarizer->summarize()]);
    }

    public function store(PlaceOrderRequest $request, PlaceOrder $placeOrder): RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return to_route('cart.index');
        }

        try {
            $order = $placeOrder->handle($this->cart->lines(), $request->customer());
        } catch (InsufficientStock $e) {
            // Stock moved between the cart and the checkout: the cart is kept
            // so the customer can adjust it.
            return to_route('cart.index')->withErrors([
                'stock' => array_map(fn (Shortage $s) => $s->message(), $e->shortages),
            ]);
        }

        $this->cart->clear();

        // No customer account: a signed URL is what lets this browser, and
        // nobody guessing ids, see the confirmation.
        return redirect()->to(URL::temporarySignedRoute('orders.confirmation', now()->addDays(30), ['order' => $order]));
    }
}
