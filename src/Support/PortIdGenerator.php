<?php

/**
 * Port ID Generator.
 *
 * Generates unique PortID identifiers following NUMLEX format:
 * IDA + YYYYMMDDhhmmss + nnnn (21 characters total)
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Support
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Support;

use Carbon\CarbonImmutable;

class PortIdGenerator
{
    /**
     * Generates a new PortID.
     *
     * @param  string              $ida      IDA code (3 characters)
     * @param  CarbonImmutable|null $datetime Optional datetime (defaults to now)
     * @param  int|null             $sequence Optional sequence number (0-9999)
     * @return string              21-character PortID
     */
    public function generate(
        string $ida,
        ?CarbonImmutable $datetime = null,
        ?int $sequence = null,
    ): string {
        $datetime = $datetime ?? CarbonImmutable::now(config('alize.timezone'));
        $sequence = $sequence ?? $this->nextSequence();

        return sprintf(
            '%s%s%04d',
            $ida,
            $datetime->format('YmdHis'),
            $sequence,
        );
    }

    /**
     * Validates PortID format.
     *
     * @param  string $portId PortID to validate
     * @return bool   True if valid
     */
    public function validate(string $portId): bool
    {
        // Must be exactly 21 characters
        if (strlen($portId) !== 21) {
            return false;
        }

        // IDA (3 chars) + Timestamp (14 digits) + Sequence (4 digits)
        return preg_match('/^[A-Z0-9]{3}\d{14}\d{4}$/', $portId) === 1;
    }

    /**
     * Extracts IDA from PortID.
     *
     * @param  string $portId PortID
     * @return string IDA code
     */
    public function extractIda(string $portId): string
    {
        return substr($portId, 0, 3);
    }

    /**
     * Extracts timestamp from PortID.
     *
     * @param  string          $portId PortID
     * @return CarbonImmutable Timestamp
     */
    public function extractTimestamp(string $portId): CarbonImmutable
    {
        $timestampStr = substr($portId, 3, 14);

        $dateTime = CarbonImmutable::createFromFormat(
            'YmdHis',
            $timestampStr,
            config('alize.timezone'),
        );

        if (!$dateTime instanceof CarbonImmutable) {
            throw new \InvalidArgumentException('Invalid PortID timestamp.');
        }

        return $dateTime;
    }

    /**
     * Extracts sequence number from PortID.
     *
     * @param  string $portId PortID
     * @return int    Sequence number
     */
    public function extractSequence(string $portId): int
    {
        return (int) substr($portId, 17, 4);
    }

    /**
     * Gets next sequence number for today.
     *
     * @return int Sequence number (0-9999)
     */
    private function nextSequence(): int
    {
        // In production, this should query database for max sequence today
        // For now, return random to avoid collisions
        return random_int(1, 9999);
    }
}
