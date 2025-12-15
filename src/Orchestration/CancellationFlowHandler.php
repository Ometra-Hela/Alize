<?php

/**
 * Cancellation Flow Handler.
 *
 * Handles portability cancellation requests from RIDA.
 * Validates state, checks T4 timer, and sends cancellation message.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Orchestration
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Orchestration;

use Carbon\CarbonImmutable;
use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Enums\PortabilityState;
use Ometra\HelaAlize\Models\Portability;
use Ometra\HelaAlize\Soap\NumlexSoapClient;
use Ometra\HelaAlize\Xml\Builders\CancellationRequestBuilder;

class CancellationFlowHandler
{
    private NumlexSoapClient $soapClient;

    private StateOrchestrator $orchestrator;

    public function __construct()
    {
        $this->soapClient = new NumlexSoapClient();
        $this->orchestrator = new StateOrchestrator();
    }

    /**
     * Requests portability cancellation (sends 3001).
     *
     * @param  Portability $portability Portability to cancel
     * @param  string|null $reason Cancellation reason
     * @return void
     * @throws \Exception
     */
    public function requestCancellation(
        Portability $portability,
        ?string $reason = null,
    ): void {
        $state = PortabilityState::from($portability->state);

        // Validate state allows cancellation
        if (!$state->canCancel()) {
            throw new \Exception(
                "Cannot cancel in state: {$state->value}. " .
                "Cancellation only allowed in PORT_REQUESTED, PORT_INDVAL_REQUESTED, " .
                "READY_TO_BE_SCHEDULED, or PORT_SCHEDULED.",
            );
        }

        // Check T4 timer not expired
        if ($this->isT4Expired($portability)) {
            throw new \Exception("Cannot cancel: T4 timer has expired");
        }

        // Build cancellation message
        $builder = new CancellationRequestBuilder();
        $xml = $builder->build([
            'sender' => config('alize.ida'),
            'port_id' => $portability->port_id,
            'port_type' => $portability->port_type,
            'subscriber_type' => $portability->subscriber_type,
            'recovery_flag' => 'NO',
            'dida' => $portability->dida,
            'dcr' => $portability->dcr,
            'rida' => $portability->rida,
            'rcr' => $portability->rcr,
            'numbers' => $this->getPortabilityNumbers($portability),
            'comments' => $reason ?? 'Client requested cancellation',
        ]);

        // Send SOAP message
        $result = $this->soapClient->sendWithRetry(
            $xml,
            MessageType::CANCELLATION_REQUEST,
            $portability->port_id,
        );

        if (!$result['success']) {
            throw new \Exception("Failed to send cancellation: {$result['error']}");
        }

        \Log::info('Cancellation requested', [
            'port_id' => $portability->port_id,
            'reason' => $reason,
        ]);

        // Note: State will transition to CANCELLED when ABD sends 3002
    }

    /**
     * Checks if T4 timer has expired.
     *
     * @param  Portability $portability Portability instance
     * @return bool        True if expired
     */
    private function isT4Expired(Portability $portability): bool
    {
        if (!$portability->t4_expires_at) {
            return false;
        }

        return CarbonImmutable::parse($portability->t4_expires_at)
            ->isPast();
    }

    /**
     * Gets portability numbers for XML generation.
     *
     * @param  Portability $portability Portability instance
     * @return array       Numbers array
     */
    private function getPortabilityNumbers(Portability $portability): array
    {
        return $portability->msisdn->map(function ($msisdn) {
            return [
                'start' => $msisdn->msisdn_ported,
                'end' => $msisdn->msisdn_ported,
            ];
        })->toArray();
    }
}
