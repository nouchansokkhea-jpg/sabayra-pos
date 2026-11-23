<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * Automatically clears any cached configuration left behind by local artisan commands
     * so phpunit.xml testing environment variables (in-memory SQLite, array session, sync queue)
     * are always loaded reliably.
     */
    public function createApplication()
    {
        $cachedConfig = dirname(__DIR__).'/bootstrap/cache/config.php';
        if (file_exists($cachedConfig)) {
            @unlink($cachedConfig);
        }

        return parent::createApplication();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure CSRF token validation does not block testing requests
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }
}
