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
    public function boot()
    {
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

    public function register()
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
}
