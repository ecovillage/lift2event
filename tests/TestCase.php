<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Clear the auth guard cache between HTTP calls within the same test.
     *
     * Laravel's auth manager caches the resolved guard/user for the lifetime
     * of the application instance. In tests that make multiple requests with
     * different tokens, this cache must be flushed so each request resolves
     * its own user.
     */
    protected function flushAuthGuards(): void
    {
        $this->app->get('auth')->forgetGuards();
    }
}
