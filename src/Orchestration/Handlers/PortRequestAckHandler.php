<?php

/**
 * Port Request Acknowledgment Handler (1002).
 *
 * Processes acknowledgment from ABD for port request (1001).
 * Updates portability status based on ABD validation results.
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

class PortRequestAckHandler implements InboundMessageHandler
{
    /**
     * Handles port request acknowledgment.
     *
     * @param  NpcMessage $message Received message
     * @return void
     */
    public function handle(NpcMessage $message): void
    {
        if (!is_string($message->raw_xml) || $message->raw_xml === '') {
            Log::error('Inbound message XML is missing for ACK handler', [
                'port_id' => $message->port_id,
            ]);

            return;
        }

        $portability = Portability::where('port_id', $message->port_id)->first();

        if (!$portability) {
            Log::error('Portability not found for ACK', [
                'port_id' => $message->port_id,
            ]);

            return;
        }

        // Parse ACK using proper parser
        $parser = new \Ometra\HelaAlize\Xml\Parsers\PortRequestAckParser();
        $data = $parser->parse($message->raw_xml);

        // Store parsed data
        $message->parsed_data = $data;
        $message->save();

        if ($data['ack_status'] === 'SUCCESS') {
            // ABD accepted the request, wait for DIDA response
            $orchestrator = new StateOrchestrator();
            $orchestrator->transition(
                $portability,
                PortabilityState::PORT_REQUESTED,
                'ABD acknowledged request',
            );

            Log::info('Port request acknowledged', [
                'port_id' => $portability->port_id,
            ]);
        } else {
            // ABD rejected the request
            $orchestrator = new StateOrchestrator();
            $orchestrator->transition(
                $portability,
                PortabilityState::REJECTED,
                "ABD rejected: {$data['error_code']} - {$data['error_message']}",
            );

            Log::warning('Port request rejected by ABD', [
                'port_id' => $portability->port_id,
                'error_code' => $data['error_code'],
                'error_message' => $data['error_message'],
            ]);
        }
    }
}
