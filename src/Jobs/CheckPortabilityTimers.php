<?php

/**
 * Timer Check Job.
 *
 * Monitors portability timers and triggers actions on expiration.
 * Runs every minute to check T1, T3, T4, T5 timers.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Jobs
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Jobs;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Ometra\HelaAlize\Models\Portability;
use Ometra\HelaAlize\Orchestration\StateOrchestrator;

class CheckPortabilityTimers implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $now = CarbonImmutable::now(config('alize.timezone'));
        $orchestrator = new StateOrchestrator();

        // Check T1 timer (DIDA response window - 20 minutes)
        $this->checkTimer(
            Portability::whereNotNull('t1_expires_at')
                ->where('t1_expires_at', '<=', $now)
                ->get(),
            't1',
            $orchestrator,
        );

        // Check T3 timer (RIDA scheduling window - 24 hours)
        $this->checkTimer(
            Portability::whereNotNull('t3_expires_at')
                ->where('t3_expires_at', '<=', $now)
                ->get(),
            't3',
            $orchestrator,
        );

        // Check T4 timer (Cancellation window)
        $this->checkTimer(
            Portability::whereNotNull('t4_expires_at')
                ->where('t4_expires_at', '<=', $now)
                ->get(),
            't4',
            $orchestrator,
        );
    }

    /**
     * Checks and handles expired timers.
     *
     * @param  \Illuminate\Support\Collection $portabilities Portabilities with expired timer
     * @param  string                         $timer Timer name
     * @param  StateOrchestrator              $orchestrator Orchestrator instance
     * @return void
     */
    private function checkTimer(
        $portabilities,
        string $timer,
        StateOrchestrator $orchestrator,
    ): void {
        foreach ($portabilities as $portability) {
            try {
                $orchestrator->handleTimerExpiration($portability, $timer);

                Log::info('Timer expired and handled', [
                    'port_id' => $portability->port_id,
                    'timer' => $timer,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to handle timer expiration', [
                    'port_id' => $portability->port_id,
                    'timer' => $timer,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
