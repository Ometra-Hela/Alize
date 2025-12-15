<?php

/**
 * Package test case bootstrap.
 *
 * Provides a Laravel application context for package unit tests.
 *
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Tests
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Tests;

use Ometra\HelaAlize\HelaAlizeServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Gets the package providers for the test application.
     *
     * @param  \Illuminate\Foundation\Application $app Application instance.
     * @return array<int, class-string>            Provider class names.
     */
    protected function getPackageProviders($app): array
    {
        return [HelaAlizeServiceProvider::class];
    }

    /**
     * Defines environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app Application instance.
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('alize.ida', 'XXX');
        $app['config']->set('alize.timezone', 'America/Mexico_City');
        $app['config']->set('alize.table_prefix', 'alize_');

        // Minimal valid SOAP configuration for tests
        $app['config']->set('alize.soap.user_id', 'test-user');
        $app['config']->set('alize.soap.password_b64', 'test-pass');
        $app['config']->set('alize.soap.client_endpoint', 'https://example.test/np');
        $app['config']->set('alize.soap.tls', [
            'cert_path' => __FILE__,
            'key_path' => __FILE__,
            'ca_path' => __FILE__,
        ]);
        $app['config']->set('alize.soap.circuit_breaker', [
            'failure_threshold' => 2,
            'open_seconds' => 1,
            'half_open_successes' => 1,
        ]);
        $app['config']->set('alize.soap.retry_delay_ms', 10);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
