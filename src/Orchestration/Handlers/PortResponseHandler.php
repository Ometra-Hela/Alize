<?php

/**
 * Port Response Handler (1004).
 *
 * Processes DIDA response to portation request.
 * Handles acceptance, rejection, or partial rejection of numbers.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Orchestration\Handlers
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Orchestration\Handlers;

use Ometra\HelaAlize\Enums\PortabilityState;
use Ometra\HelaAlize\Models\NpcMessage;
use Ometra\HelaAlize\Models\Portability;
use Ometra\HelaAlize\Orchestration\StateOrchestrator;

class PortResponseHandler
{
    /**
     * Handles port response from DIDA.
     *
     * @param  NpcMessage $message Received message
     * @return void
     */
    public function handle(NpcMessage $message): void
    {
        $portability = Portability::where('port_id', $message->port_id)->first();

        if (!$portability) {
            \Log::error('Portability not found for response', [
                'port_id' => $message->port_id,
            ]);

            return;
        }

        // Parse response using proper parser
        $parser = new \Ometra\HelaAlize\Xml\Parsers\PortResponseParser();
        $data = $parser->parse($message->raw_xml);

        // Store parsed data
        $message->parsed_data = $data;
        $message->save();

        $orchestrator = new StateOrchestrator();

        if ($data['status'] === 'ACCEPT') {
            // DIDA accepted, wait for ABD to confirm ready to schedule
            \Log::info('DIDA accepted portation', [
                'port_id' => $portability->port_id,
            ]);

            // ABD will send 1005 if accepted
        } elseif ($data['status'] === 'REJECT') {
            // DIDA rejected
            $orchestrator->transition(
                $portability,
                PortabilityState::REJECTED,
                "DIDA rejected: {$data['reason_code']} - {$data['reason_text']}",
            );

            \Log::warning('DIDA rejected portation', [
                'port_id' => $portability->port_id,
                'reason_code' => $data['reason_code'],
                'reason_text' => $data['reason_text'],
            ]);
        } elseif ($data['status'] === 'PARTIAL_REJECT') {
            // Some numbers accepted, some rejected
            \Log::info('DIDA partial rejection', [
                'port_id' => $portability->port_id,
                'rejected_numbers' => $data['rejected_numbers'],
            ]);

            // Update specific MSISDNs as rejected
            foreach ($data['rejected_numbers'] as $rejected) {
                $msisdn = $portability->msisdn()
                    ->where('msisdn_ported', $rejected['number'])
                    ->first();

                if ($msisdn) {
                    $msisdn->status = 'REJECTED';
                    $msisdn->rejection_reason = $rejected['reason_code'];
                    $msisdn->save();
                }
            }

            // Continue with accepted numbers
        }
    }
}
