<?php

/**
 * Portation Flow Handler.
 *
 * Orchestrates the complete portability flow from initiation to completion.
 * Handles RIDA perspective: request → schedule → execute.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Orchestration
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Orchestration;

use Carbon\CarbonImmutable;
use Ometra\HelaAlize\Classes\Calendar\BusinessCalendarMX;
use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Enums\PortabilityState;
use Ometra\HelaAlize\Models\Portability;
use Ometra\HelaAlize\Soap\NumlexSoapClient;
use Ometra\HelaAlize\Support\FolioIdGenerator;
use Ometra\HelaAlize\Support\PortIdGenerator;
use Ometra\HelaAlize\Xml\Builders\PortRequestBuilder;
use Ometra\HelaAlize\Xml\Builders\SchedulePortBuilder;

class PortationFlowHandler
{
    private NumlexSoapClient $soapClient;

    private StateOrchestrator $orchestrator;

    private PortIdGenerator $portIdGenerator;

    private FolioIdGenerator $folioIdGenerator;

    private BusinessCalendarMX $calendar;

    public function __construct()
    {
        $this->soapClient = new NumlexSoapClient();
        $this->orchestrator = new StateOrchestrator();
        $this->portIdGenerator = new PortIdGenerator();
        $this->folioIdGenerator = new FolioIdGenerator();
        $this->calendar = new BusinessCalendarMX();
    }

    /**
     * Initiates portability request (sends 1001).
     *
     * @param  array{
     *   port_type: string,
     *   subscriber_type: string,
     *   recovery_flag: string,
     *   dida: string,
     *   dcr: string,
     *   rcr: string,
     *   numbers: array<array{start: string, end: string}>,
     *   pin?: string,
     *   subs_req_time: string,
     *   comments?: string
     * } $data Portability data
     * @return Portability Created portability
     */
    public function initiatePortation(array $data): Portability
    {
        $ida = config('alize.ida');
        $portId = $this->portIdGenerator->generate($ida);
        $folioId = $this->folioIdGenerator->generate($ida);

        // Calculate requested execution date (next business day + 1)
        $now = CarbonImmutable::now(config('alize.timezone'));
        $reqExecDate = $this->calendar->addBusinessDays(
            $this->calendar->clampToWorkingWindow($now),
            2,
        );

        // Build port request XML
        $builder = new PortRequestBuilder();
        $xml = $builder->build([
            'sender' => $ida,
            'port_id' => $portId,
            'folio_id' => $folioId,
            'port_type' => $data['port_type'],
            'subscriber_type' => $data['subscriber_type'],
            'recovery_flag' => $data['recovery_flag'] ?? 'NO',
            'dida' => $data['dida'],
            'dcr' => $data['dcr'],
            'rida' => $ida,
            'rcr' => $data['rcr'],
            'numbers' => $data['numbers'],
            'pin' => $data['pin'] ?? null,
            'req_port_exec_date' => $reqExecDate->format('YmdHis'),
            'subs_req_time' => $data['subs_req_time'],
            'comments' => $data['comments'] ?? null,
        ]);

        // Create portability record
        $portability = Portability::create([
            'port_id' => $portId,
            'folio_id' => $folioId,
            'state' => PortabilityState::INITIAL->value,
            'port_type' => $data['port_type'],
            'subscriber_type' => $data['subscriber_type'],
            'dida' => $data['dida'],
            'dcr' => $data['dcr'],
            'rida' => $ida,
            'rcr' => $data['rcr'],
            'req_port_exec_date' => $reqExecDate,
            'created_at' => $now,
        ]);

        // Persist numbers
        foreach ($data['numbers'] as $range) {
            // Expand range if needed or strict list?
            // ABD allows ranges, but typically we might want to store individual numbers or ranges.
            // For now, let's assume we store individual numbers if the range is small,
            // OR we need to adjust PortabilityNumber to store ranges.
            // Looking at getPortabilityNumbers implementation, it mapped 'msisdn_ported' to start/end = same.
            // This implies the DB stores individual numbers.

            // Loop through range
            for ($i = (int)$range['start']; $i <= (int)$range['end']; $i++) {
                \Ometra\HelaAlize\Models\PortabilityNumber::create([
                    'portability_id' => $portability->id_portability,
                    'msisdn_ported' => (string)$i
                ]);
            }
        }

        // Send SOAP message
        $result = $this->soapClient->sendWithRetry(
            $xml,
            MessageType::PORT_REQUEST,
            $portId,
        );

        if (!$result['success']) {
            throw new \Exception("Failed to send port request: {$result['error']}");
        }

        \Log::info('Portation initiated', [
            'port_id' => $portId,
            'folio_id' => $folioId,
        ]);

        return $portability;
    }

    /**
     * Schedules portability (sends 1006).
     *
     * @param  Portability                  $portability Portability instance
     * @param  CarbonImmutable|null          $execDate Execution date (null = auto-calculate)
     * @return void
     * @throws \Exception
     */
    public function schedulePortation(
        Portability $portability,
        ?CarbonImmutable $execDate = null,
    ): void {
        // Verify state allows scheduling
        $state = PortabilityState::from($portability->state);
        if ($state !== PortabilityState::READY_TO_BE_SCHEDULED) {
            throw new \Exception("Cannot schedule in state: {$state->value}");
        }

        // Calculate execution date if not provided
        if ($execDate === null) {
            $execDate = $this->calculateExecutionDate();
        }

        // Build schedule message
        $builder = new SchedulePortBuilder();
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
            'port_exec_date' => $execDate->format('YmdHis'),
            'req_port_exec_date' => $portability->req_port_exec_date->format('YmdHis'),
        ]);

        // Send SOAP message
        $result = $this->soapClient->sendWithRetry(
            $xml,
            MessageType::SCHEDULE_PORT_REQUEST,
            $portability->port_id,
        );

        if (!$result['success']) {
            throw new \Exception("Failed to schedule: {$result['error']}");
        }

        // Update portability
        $portability->port_exec_date = $execDate;
        $portability->save();

        \Log::info('Portation scheduled', [
            'port_id' => $portability->port_id,
            'exec_date' => $execDate->toDateTimeString(),
        ]);
    }

    /**
     * Calculates valid execution date per ABD rules.
     *
     * @return CarbonImmutable Execution date
     */
    private function calculateExecutionDate(): CarbonImmutable
    {
        $now = CarbonImmutable::now(config('alize.timezone'));
        $cutoffTime = $now->setTimeFromTimeString('21:59');

        // If before 21:59, next business day. Otherwise, +2 business days.
        $daysToAdd = $now->lessThan($cutoffTime) ? 1 : 2;

        return $this->calendar->addBusinessDays($now, $daysToAdd)
            ->setTimeFromTimeString('12:00');
    }

    /**
     * Gets portability numbers for XML generation.
     *
     * @param  Portability     $portability Portability instance
     * @return array<array{start: string, end: string}>
     */
    private function getPortabilityNumbers(Portability $portability): array
    {
        return $portability->numbers->map(function ($number) {
            return [
                'start' => $number->msisdn_ported,
                'end' => $number->msisdn_ported,
            ];
        })->toArray();
    }
}
