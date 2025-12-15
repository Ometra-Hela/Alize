<?php

/**
 * Main Service API for HELA Alize.
 *
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Services
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Services;

use Ometra\HelaAlize\Enums\PortabilityState;
use Ometra\HelaAlize\Models\Portability;
use Ometra\HelaAlize\Orchestration\CancellationFlowHandler;
use Ometra\HelaAlize\Orchestration\NipFlowHandler;
use Ometra\HelaAlize\Orchestration\PortationFlowHandler;
use Ometra\HelaAlize\Orchestration\ReversionFlowHandler;
use Ometra\HelaAlize\Orchestration\StateOrchestrator;

/**
 * Main Service API for HELA Alize.
 *
 * Provides a unified interface for the host application to interact with
 * the portability system.
 */
class HelaAlizeService
{
    public function __construct(
        protected PortationFlowHandler $flowHandler,
        protected CancellationFlowHandler $cancellationHandler,
        protected NipFlowHandler $nipHandler,
        protected ReversionFlowHandler $reversionHandler,
        protected StateOrchestrator $orchestrator
    ) {
    }

    /**
     * Initiates a new portability request (1001).
     *
     * @param array{
     *   port_type: string,
     *   subscriber_type: string,
     *   recovery_flag?: string,
     *   dida: string,
     *   dcr: string,
     *   rcr: string,
     *   numbers: array<array{start: string, end: string}>,
     *   pin?: string,
     *   subs_req_time: string,
     *   comments?: string
     * } $data Portability data
     * @return Portability
     */
    public function initiate(array $data): Portability
    {
        if (!array_key_exists('recovery_flag', $data)) {
            $data['recovery_flag'] = 'NO';
        }

        return $this->flowHandler->initiatePortation($data);
    }

    /**
     * Schedules a portability for execution (1006).
     *
     * @param Portability $portability
     * @return void
     */
    public function schedule(Portability $portability): void
    {
        $this->flowHandler->schedulePortation($portability);
    }

    /**
     * Cancels a portability request (3001).
     *
     * @param Portability $portability
     * @param string $reason
     * @return void
     */
    public function cancel(Portability $portability, string $reason): void
    {
        $this->cancellationHandler->requestCancellation($portability, $reason);
    }

    /**
     * Requests NIP generation (2001).
     *
     * @param string $msisdn
     * @param string|null $dida Override DIDA
     * @return array
     */
    public function requestNip(string $msisdn, ?string $dida = null): array
    {
        return $this->nipHandler->requestNip($msisdn, $dida);
    }

    /**
     * Requests Reversion (4001).
     *
     * @param Portability $portability
     * @param string|null $reason
     * @return array
     */
    public function requestReversion(Portability $portability, ?string $reason = null): array
    {
        return $this->reversionHandler->requestReversion($portability, $reason);
    }

    /**
     * Gets the current state of a portability.
     *
     * @param string $portId
     * @return PortabilityState|null
     */
    public function getState(string $portId): ?PortabilityState
    {
        $portability = Portability::where('port_id', $portId)->first();

        if (!$portability) {
            return null;
        }

        if (!is_string($portability->state) || $portability->state === '') {
            return null;
        }

        return PortabilityState::tryFrom($portability->state);
    }

    /**
     * Finds a portability by PortID.
     *
     * @param string $portId
     * @return Portability|null
     */
    public function findByPortId(string $portId): ?Portability
    {
        return Portability::where('port_id', $portId)->first();
    }

    /**
     * Finds a portability by FolioID.
     *
     * @param string $folioId
     * @return Portability|null
     */
    public function findByFolioId(string $folioId): ?Portability
    {
        return Portability::where('folio_id', $folioId)->first();
    }
}
