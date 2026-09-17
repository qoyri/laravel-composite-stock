<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Pages render without a front-end build.
        $this->withoutVite();
    }

    /**
     * Each simulated request starts with fresh scoped services (the cart and
     * its memoised summary), as it would under PHP-FPM or Octane. Without
     * this, a summary computed during one test request leaks into the next.
     *
     * @param  array<mixed>  $parameters
     * @param  array<mixed>  $cookies
     * @param  array<mixed>  $files
     * @param  array<mixed>  $server
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app?->forgetScopedInstances();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }
}
