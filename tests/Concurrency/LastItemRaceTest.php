<?php

declare(strict_types=1);

use App\Actions\Orders\PlaceOrder;
use App\Models\Order;
use App\Stock\InsufficientStock;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/*
 * Two real processes, two PostgreSQL sessions, one last T-shirt.
 *
 * What makes it deterministic rather than "usually passes":
 *  - Start gate: the parent holds an advisory lock; both children block on it
 *    and the parent only releases it once pg_locks shows both waiting.
 *  - Forced overlap: the first child to hold the stock locks does not proceed
 *    until pg_stat_activity shows another session waiting on a lock. The
 *    contention is observed, not assumed, and recorded for the assertion.
 *  - Whichever child wins, the expected outcome is the same.
 *
 * Sockets: the parent disconnects before forking, children open their own
 * connection and end with SIGKILL, so no inherited PDO destructor can close
 * a session that belongs to another process.
 */

const RACE_GATE_KEY = 7_340_001;

beforeEach(function () {
    if (! function_exists('pcntl_fork') || ! function_exists('posix_kill')) {
        $this->markTestSkipped('Requires the pcntl and posix extensions.');
    }

    Queue::fake();
});

function raceChild(string $dir, int $productId, int $variantId): never
{
    $outcome = 'error';

    try {
        DB::select('SELECT pg_advisory_lock_shared(?)', [RACE_GATE_KEY]);

        DB::listen(function (QueryExecuted $query) use ($dir) {
            // Right after this child locked the markings, i.e. while it holds every stock lock.
            if (! str_contains($query->sql, 'from "markings" where')) {
                return;
            }
            // Only the first child to get here waits; the second one arrives after the commit.
            $holder = @fopen($dir.'/holder', 'x');
            if ($holder === false) {
                return;
            }
            fclose($holder);

            $deadline = microtime(true) + 10;
            while (microtime(true) < $deadline) {
                $waiting = DB::scalar(
                    "SELECT count(*) FROM pg_stat_activity
                     WHERE datname = current_database() AND wait_event_type = 'Lock' AND pid <> pg_backend_pid()"
                );
                if ($waiting > 0) {
                    file_put_contents($dir.'/contention', 'observed');

                    return;
                }
                usleep(5_000);
            }
        });

        app(PlaceOrder::class)->handle(
            [new App\Cart\CartLine($productId, $variantId, 1)],
            customer(),
        );
        $outcome = 'placed';
    } catch (InsufficientStock) {
        $outcome = 'refused';
    } catch (Throwable $e) {
        $outcome = 'error: '.$e->getMessage();
    }

    file_put_contents($dir.'/'.getmypid(), $outcome);
    posix_kill(getmypid(), SIGKILL);
    exit(1); // unreachable
}

it('sells the last item exactly once when two checkouts race for it', function () {
    [$product, $variant, $marking] = sellable(variantStock: 1, markingStock: 10);

    $dir = sys_get_temp_dir().'/race-'.bin2hex(random_bytes(6));
    mkdir($dir);

    $gate = $this->probe();
    $gate->select('SELECT pg_advisory_lock(?)', [RACE_GATE_KEY]);

    DB::disconnect();   // the children must open their own sessions

    $pids = [];
    foreach ([1, 2] as $_) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('fork failed');
        }
        if ($pid === 0) {
            raceChild($dir, $product->id, $variant->id);
        }
        $pids[] = $pid;
    }

    try {
        // Open the gate only once both children are queued behind it.
        $deadline = microtime(true) + 10;
        do {
            $queued = $gate->scalar(
                "SELECT count(*) FROM pg_locks WHERE locktype = 'advisory' AND objid = ? AND NOT granted",
                [RACE_GATE_KEY],
            );
            usleep(5_000);
        } while ($queued < 2 && microtime(true) < $deadline);
        expect($queued)->toBe(2, 'Both children should be waiting at the start gate.');

        $gate->select('SELECT pg_advisory_unlock(?)', [RACE_GATE_KEY]);

        foreach ($pids as $i => $pid) {
            $deadline = microtime(true) + 20;
            while (pcntl_waitpid($pid, $status, WNOHANG) === 0) {
                if (microtime(true) > $deadline) {
                    posix_kill($pid, SIGKILL);
                    throw new RuntimeException("Child {$pid} did not finish.");
                }
                usleep(5_000);
            }
            unset($pids[$i]);
        }
    } finally {
        foreach ($pids as $pid) {
            posix_kill($pid, SIGKILL);
            pcntl_waitpid($pid, $status);
        }
    }

    $outcomes = collect(glob($dir.'/[0-9]*') ?: [])->map(fn ($f) => file_get_contents($f))->sort()->values()->all();
    $contention = @file_get_contents($dir.'/contention');
    array_map('unlink', glob($dir.'/*') ?: []);
    rmdir($dir);

    expect($outcomes)->toBe(['placed', 'refused'])
        ->and($contention)->toBe('observed')
        ->and($variant->fresh()->stock)->toBe(0)
        ->and($marking->fresh()->stock)->toBe(9)
        ->and(Order::count())->toBe(1);
});
