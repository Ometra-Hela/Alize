<?php

/**
 * Service Provider for HELA Alize package.
 *
 * Registers package services, routes, migrations, and commands.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class HelaAlizeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->validateConfiguration();

        $this->loadRoutesFrom(__DIR__ . '/../routes/alize.php');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes(
            [
                __DIR__ . '/../config/alize.php' => config_path('alize.php'),
            ],
            'alize-config',
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Ometra\HelaAlize\Console\Commands\ReconcileDailyFiles::class,
                \Ometra\HelaAlize\Console\Commands\CheckConnection::class,
                \Ometra\HelaAlize\Console\Commands\TestInitiatePortability::class,
                \Ometra\HelaAlize\Console\Commands\TestFullFlowPortability::class,
            ]);
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);

            // Check portability timers every minute
            $schedule->job(new \Ometra\HelaAlize\Jobs\CheckPortabilityTimers())->everyMinute();

            // Daily reconciliation at 23:00
            $schedule->command('numlex:reconcile')->dailyAt('23:00');
        });
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/alize.php',
            'alize'
        );

        $this->app->bind('hela-alize', function ($app) {
            $soapClient = new \Ometra\HelaAlize\Soap\NumlexSoapClient();

            return new \Ometra\HelaAlize\Services\HelaAlizeService(
                new \Ometra\HelaAlize\Orchestration\PortationFlowHandler(),
                new \Ometra\HelaAlize\Orchestration\CancellationFlowHandler(),
                new \Ometra\HelaAlize\Orchestration\NipFlowHandler($soapClient),
                new \Ometra\HelaAlize\Orchestration\ReversionFlowHandler($soapClient),
                new \Ometra\HelaAlize\Orchestration\StateOrchestrator()
            );
        });
    }

    /**
     * Validates required configuration and fails fast on invalid values.
     */
    private function validateConfiguration(): void
    {
        $config = (array) config('alize.soap', []);

        $userId = (string) ($config['user_id'] ?? '');
        $passwordB64 = (string) ($config['password_b64'] ?? '');
        $endpoint = (string) ($config['client_endpoint'] ?? '');

        if ($userId === '' || $passwordB64 === '') {
            throw new \Ometra\HelaAlize\Exceptions\InvalidConfigurationException(
                'Missing NUMLEX credentials (alize.soap.user_id/password_b64)'
            );
        }

        if ($endpoint === '') {
            throw new \Ometra\HelaAlize\Exceptions\InvalidConfigurationException(
                'Missing NUMLEX endpoint (alize.soap.client_endpoint)'
            );
        }

        /** @var array<string, string> $tls */
        $tls = (array) ($config['tls'] ?? []);
        $certPath = (string) ($tls['cert_path'] ?? '');
        $keyPath = (string) ($tls['key_path'] ?? '');
        $caPath = (string) ($tls['ca_path'] ?? '');

        if ($certPath === '' || $keyPath === '' || $caPath === '') {
            throw new \Ometra\HelaAlize\Exceptions\InvalidConfigurationException(
                'Missing TLS certificate paths (alize.soap.tls.cert_path/key_path/ca_path)'
            );
        }

        foreach ([$certPath, $keyPath, $caPath] as $path) {
            if (!file_exists($path)) {
                throw new \Ometra\HelaAlize\Exceptions\InvalidConfigurationException(
                    'TLS file not found: ' . $path
                );
            }
        }

        /** @var array<string, int> $cb */
        $cb = (array) ($config['circuit_breaker'] ?? []);
        $failureThreshold = (int) ($cb['failure_threshold'] ?? 0);
        $openSeconds = (int) ($cb['open_seconds'] ?? 0);
        $halfOpenSuccesses = (int) ($cb['half_open_successes'] ?? 0);

        if ($failureThreshold < 1 || $openSeconds < 1 || $halfOpenSuccesses < 1) {
            throw new \Ometra\HelaAlize\Exceptions\InvalidConfigurationException(
                'Invalid circuit breaker configuration (thresholds must be >= 1)'
            );
        }
    }
}
