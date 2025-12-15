<?php

/**
 * Schedule Notification Handler (1007).
 *
 * Processes ABD confirmation that portability has been scheduled.
 * Marks execution date and transitions to PORT_SCHEDULED state.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Orchestration\Handlers
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Orchestration\Handlers;

use Carbon\CarbonImmutable;
use Ometra\HelaAlize\Enums\PortabilityState;
use Ometra\HelaAlize\Models\NpcMessage;
use Ometra\HelaAlize\Models\Portability;
use Ometra\HelaAlize\Orchestration\StateOrchestrator;

class ScheduleNotificationHandler
{
    /**
     * Handles schedule confirmation from ABD.
     *
     * @param  NpcMessage $message Received message
     * @return void
     */
    public function handle(NpcMessage $message): void
    {
        $portability = Portability::where('port_id', $message->port_id)->first();

        if (!$portability) {
            \Log::error('Portability not found for schedule notification', [
                'port_id' => $message->port_id,
            ]);

            return;
        }

        // Parse execution date from XML
        $execDate = $this->parseExecutionDate($message->raw_xml);

        $orchestrator = new StateOrchestrator();
        $orchestrator->transition(
            $portability,
            PortabilityState::PORT_SCHEDULED,
            'ABD confirmed scheduling',
        );

        // Update execution date
        $portability->port_exec_date = $execDate;
        $portability->save();

        \Log::info('Portation scheduling confirmed', [
            'port_id' => $portability->port_id,
            'exec_date' => $execDate->toDateTimeString(),
        ]);

        // Dispatch event for host application to send notifications
        \Ometra\HelaAlize\Events\PortabilityScheduled::dispatch($portability);
    }

    /**
     * Parses execution date from XML.
     *
     * @param  string          $xml XML content
     * @return CarbonImmutable Execution date
     */
    private function parseExecutionDate(string $xml): CarbonImmutable
    {
        // Simplified parsing - in production, use proper XML parser
        preg_match('/<PortExecDate>(\d{14})<\/PortExecDate>/', $xml, $matches);

        if (isset($matches[1])) {
            return CarbonImmutable::createFromFormat(
                'YmdHis',
                $matches[1],
                config('alize.timezone'),
            );
        }

        return CarbonImmutable::now(config('alize.timezone'));
    }
}
