<?php

/**
 * Ready to Schedule Handler (1005).
 *
 * Processes ABD notification that portability is ready to be scheduled.
 * Triggers T3 timer and allows RIDA to send scheduling message (1006).
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Orchestration\Handlers
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Orchestration\Handlers;

use Illuminate\Support\Facades\Log;
use Ometra\HelaAlize\Enums\PortabilityState;
use Ometra\HelaAlize\Models\NpcMessage;
use Ometra\HelaAlize\Models\Portability;
use Ometra\HelaAlize\Orchestration\StateOrchestrator;

class ReadyToScheduleHandler implements InboundMessageHandler
{
    /**
     * Handles ready to schedule notification.
     *
     * @param  NpcMessage $message Received message
     * @return void
     */
    public function handle(NpcMessage $message): void
    {
        $portability = Portability::where('port_id', $message->port_id)->first();

        if (!$portability) {
            Log::error('Portability not found for ready-to-schedule', [
                'port_id' => $message->port_id,
            ]);

            return;
        }

        $orchestrator = new StateOrchestrator();
        $orchestrator->transition(
            $portability,
            PortabilityState::READY_TO_BE_SCHEDULED,
            'ABD confirmed ready to schedule',
        );

        Log::info('Portability ready to schedule', [
            'port_id' => $portability->port_id,
            't3_expires_at' => $portability->t3_expires_at,
        ]);

        // Dispatch event for host application to notify admin/user to schedule
        \Ometra\HelaAlize\Events\PortabilityReadyToSchedule::dispatch($portability);
    }
}
