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
use Illuminate\Support\Facades\Log;
use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Enums\PortabilityState;
use Ometra\HelaAlize\Models\Portability;
use Ometra\HelaAlize\Models\PortabilityNumber;
use Ometra\HelaAlize\Soap\NumlexSoapClient;
use Ometra\HelaAlize\Xml\Builders\CancellationRequestBuilder;

class CancellationFlowHandler
{
    private NumlexSoapClient $soapClient;

    public function __construct()
    {
        $this->soapClient = new NumlexSoapClient();
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
        if (!is_string($portability->state) || $portability->state === '') {
            throw new \InvalidArgumentException('Portability state is missing.');
        }

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
        $ida = (string) config('alize.ida');
        if ($ida === '') {
            throw new \InvalidArgumentException('Missing sender IDA configuration (alize.ida).');
        }

        $portId = $this->requireString($portability->port_id, 'port_id');

        $builder = new CancellationRequestBuilder();
        $xml = $builder->build([
            'sender' => $ida,
            'port_id' => $portId,
            'port_type' => $this->requireString($portability->port_type, 'port_type'),
            'subscriber_type' => $this->requireString($portability->subscriber_type, 'subscriber_type'),
            'recovery_flag' => 'NO',
            'dida' => $this->requireString($portability->dida, 'dida'),
            'dcr' => $this->requireString($portability->dcr, 'dcr'),
            'rida' => $this->requireString($portability->rida, 'rida'),
            'rcr' => $this->requireString($portability->rcr, 'rcr'),
            'numbers' => $this->getPortabilityNumbers($portability),
            'comments' => $reason ?? 'Client requested cancellation',
        ]);

        // Send SOAP message
        $result = $this->soapClient->sendWithRetry(
            $xml,
            MessageType::CANCELLATION_REQUEST,
            $portId,
        );

        if (!$result['success']) {
            throw new \Exception("Failed to send cancellation: {$result['error']}");
        }

        Log::info('Cancellation requested', [
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
        $numbers = [];

        foreach ($portability->numbers()->get() as $number) {
            if (!$number instanceof PortabilityNumber) {
                continue;
            }

            $msisdn = $number->msisdn_ported;
            if (!is_string($msisdn) || $msisdn === '') {
                continue;
            }

            $numbers[] = [
                'start' => $msisdn,
                'end' => $msisdn,
            ];
        }

        return $numbers;
    }

    /**
     * Requires a non-empty string value.
     *
     * @param  string|null $value Value to validate
     * @param  string      $name  Field name
     * @return string
     */
    private function requireString(?string $value, string $name): string
    {
        if ($value === null || $value === '') {
            throw new \InvalidArgumentException("Missing required portability field: {$name}.");
        }

        return $value;
    }
}
