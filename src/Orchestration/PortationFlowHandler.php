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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ometra\HelaAlize\Classes\Calendar\BusinessCalendarMX;
use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Enums\PortabilityState;
use Ometra\HelaAlize\Exceptions\IntegrationException;
use Ometra\HelaAlize\Models\Portability;
use Ometra\HelaAlize\Models\PortabilityNumber;
use Ometra\HelaAlize\Soap\NumlexSoapClient;
use Ometra\HelaAlize\Support\FolioIdGenerator;
use Ometra\HelaAlize\Support\PortIdGenerator;
use Ometra\HelaAlize\Xml\Builders\PortRequestBuilder;
use Ometra\HelaAlize\Xml\Builders\SchedulePortBuilder;

class PortationFlowHandler
{
    private NumlexSoapClient $soapClient;

    private PortIdGenerator $portIdGenerator;

    private FolioIdGenerator $folioIdGenerator;

    private BusinessCalendarMX $calendar;

    public function __construct()
    {
        $this->soapClient = new NumlexSoapClient();
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
     * @throws IntegrationException When SOAP request fails
     */
    public function initiatePortation(array $data): Portability
    {
        return DB::transaction(function () use ($data) {
            $ida = (string) config('alize.ida');
            if ($ida === '') {
                throw new IntegrationException('Missing sender IDA configuration (alize.ida).');
            }

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
            $payload = [
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
                'req_port_exec_date' => $reqExecDate->format('YmdHis'),
                'subs_req_time' => $data['subs_req_time'],
            ];

            if (isset($data['pin']) && is_string($data['pin']) && $data['pin'] !== '') {
                $payload['pin'] = $data['pin'];
            }

            if (isset($data['comments']) && is_string($data['comments']) && $data['comments'] !== '') {
                $payload['comments'] = $data['comments'];
            }

            $xml = $builder->build($payload);

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

            // Persist numbers with batch inserts (performance optimization)
            $this->batchInsertNumbers($portability, $data['numbers']);

            // Send SOAP message
            $result = $this->soapClient->sendWithRetry(
                $xml,
                MessageType::PORT_REQUEST,
                $portId,
            );

            if (!$result['success']) {
                throw new IntegrationException(
                    "Failed to send port request: {$result['error']}"
                );
            }

            Log::info('Portation initiated', [
                'port_id' => $portId,
                'folio_id' => $folioId,
            ]);

            return $portability;
        });
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
        if (!is_string($portability->state) || $portability->state === '') {
            throw new \InvalidArgumentException('Portability state is missing.');
        }

        $state = PortabilityState::from($portability->state);
        if ($state !== PortabilityState::READY_TO_BE_SCHEDULED) {
            throw new \Exception("Cannot schedule in state: {$state->value}");
        }

        // Calculate execution date if not provided
        if ($execDate === null) {
            $execDate = $this->calculateExecutionDate();
        }

        $ida = (string) config('alize.ida');
        if ($ida === '') {
            throw new \InvalidArgumentException('Missing sender IDA configuration (alize.ida).');
        }

        $portId = $this->requireString($portability->port_id, 'port_id');

        if (!$portability->req_port_exec_date instanceof \Carbon\Carbon) {
            throw new \InvalidArgumentException('Portability req_port_exec_date is missing.');
        }

        // Build schedule message
        $builder = new SchedulePortBuilder();
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
            'port_exec_date' => $execDate->format('YmdHis'),
            'req_port_exec_date' => $portability->req_port_exec_date->format('YmdHis'),
        ]);

        // Send SOAP message
        $result = $this->soapClient->sendWithRetry(
            $xml,
            MessageType::SCHEDULE_PORT_REQUEST,
            $portId,
        );

        if (!$result['success']) {
            throw new \Exception("Failed to schedule: {$result['error']}");
        }

        // Update portability
        $portability->port_exec_date = $execDate->toMutable();
        $portability->save();

        Log::info('Portation scheduled', [
            'port_id' => $portability->port_id,
            'exec_date' => $execDate->toDateTimeString(),
        ]);
    }

    /**
     * Batch inserts portability numbers with performance optimization.
     *
     * Prevents memory issues with large number ranges by batching inserts.
     *
     * @param  Portability                              $portability Portability instance
     * @param  array<array{start: string, end: string}> $ranges      Number ranges
     * @return void
     */
    private function batchInsertNumbers(Portability $portability, array $ranges): void
    {
        $batchSize = 1000;
        $numbers = [];

        foreach ($ranges as $range) {
            $start = (int)$range['start'];
            $end = (int)$range['end'];
            $rangeSize = $end - $start + 1;

            // Warn about very large ranges
            if ($rangeSize > 10000) {
                Log::warning('Large number range detected', [
                    'range_size' => $rangeSize,
                    'start' => $start,
                    'end' => $end,
                    'port_id' => $portability->port_id,
                ]);
            }

            for ($i = $start; $i <= $end; $i++) {
                $numbers[] = [
                    'portability_id' => $portability->id_portability,
                    'msisdn_ported' => (string)$i,
                ];

                // Batch insert every N records
                if (count($numbers) >= $batchSize) {
                    PortabilityNumber::insert($numbers);
                    $numbers = [];
                }
            }
        }

        // Insert remaining numbers
        if (!empty($numbers)) {
            PortabilityNumber::insert($numbers);
        }
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
