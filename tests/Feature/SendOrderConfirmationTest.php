<?php

declare(strict_types=1);

use App\Actions\Orders\PlaceOrder;
use App\Jobs\SendOrderConfirmation;
use App\Mail\OrderConfirmed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

it('is a queued job', function () {
    expect(is_subclass_of(SendOrderConfirmation::class, ShouldQueue::class))->toBeTrue();
});

it('sends the confirmation email to the customer', function () {
    Queue::fake();
    Mail::fake();
    [$product, $variant] = sellable(priceCents: 2500);
    $order = app(PlaceOrder::class)->handle([line($product, $variant, 2)], customer());

    (new SendOrderConfirmation($order))->handle();

    Mail::assertSent(OrderConfirmed::class, function (OrderConfirmed $mail) use ($order) {
        return $mail->hasTo('lea.muller@example.ch')
            && $mail->order->is($order)
            && str_contains($mail->render(), $order->reference)
            && str_contains($mail->render(), '50.– CHF');
    });
});

it('is stored on the database queue once the order is committed', function () {
    config(['queue.default' => 'database']);
    [$product, $variant] = sellable();

    app(PlaceOrder::class)->handle([line($product, $variant)], customer());

    $job = DB::table('jobs')->sole();
    expect($job->queue)->toBe('default')
        ->and($job->payload)->toContain('SendOrderConfirmation');
});

it('retries with backoff', function () {
    [$product, $variant] = sellable();
    Queue::fake();
    $order = app(PlaceOrder::class)->handle([line($product, $variant)], customer());

    $job = new SendOrderConfirmation($order);

    expect($job->tries)->toBe(5)
        ->and($job->backoff())->toBe([10, 60, 300, 900]);
});
