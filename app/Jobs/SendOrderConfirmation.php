<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\OrderConfirmed;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Sends the order confirmation outside the HTTP request.
 *
 * Dispatched with afterCommit(): a worker can never pick up an order whose
 * transaction was finally rolled back. SMTP hiccups are retried with backoff
 * without ever touching the checkout.
 */
class SendOrderConfirmation implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public Order $order) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function handle(): void
    {
        $this->order->loadMissing('lines');

        Mail::to($this->order->email)->send(new OrderConfirmed($this->order));
    }
}
