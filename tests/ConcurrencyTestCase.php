<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;

/**
 * Base for tests that need real, committed transactions.
 *
 * RefreshDatabase wraps each test in a transaction that is never committed:
 * a second connection would not see the data, and nested DB::transaction()
 * calls become savepoints. Here data is committed and the tables are
 * truncated before and after each test.
 */
abstract class ConcurrencyTestCase extends TestCase
{
    use DatabaseTruncation;

    /** SQLSTATE 55P03: lock_not_available (raised on lock_timeout / NOWAIT). */
    public const LOCK_NOT_AVAILABLE = '55P03';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.probe' => config('database.connections.pgsql')]);
    }

    protected function tearDown(): void
    {
        DB::disconnect('probe');
        $this->truncateTablesForAllConnections();

        parent::tearDown();
    }

    /** A second, independent PostgreSQL session. */
    protected function probe(): Connection
    {
        return DB::connection('probe');
    }

    /**
     * Tries to take $lockClause on one row from the probe session, giving up
     * after $timeout. Returns true if the row was free, false if it was locked.
     */
    protected function canLock(string $table, int $id, string $lockClause = 'FOR UPDATE', string $timeout = '100ms'): bool
    {
        $probe = $this->probe();
        $probe->beginTransaction();

        try {
            $probe->statement("SET LOCAL lock_timeout = '{$timeout}'");
            $probe->select("SELECT id FROM {$table} WHERE id = ? {$lockClause}", [$id]);

            return true;
        } catch (QueryException $e) {
            if ($e->getCode() === self::LOCK_NOT_AVAILABLE) {
                return false;
            }
            throw $e;
        } finally {
            $probe->rollBack();
        }
    }
}
