-- Separate database for the test suite, so `php artisan test` never touches dev data.
SELECT 'CREATE DATABASE composite_stock_testing OWNER composite'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'composite_stock_testing')\gexec
