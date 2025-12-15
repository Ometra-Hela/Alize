<?php

/**
 * Folio ID Generator.
 *
 * Generates unique FolioID identifiers following NUMLEX format:
 * IDA + AAMMDDhhmm + nnnnn (18 characters total)
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Support
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Support;

use Carbon\CarbonImmutable;

class FolioIdGenerator
{
    /**
     * Generates a new FolioID.
     *
     * @param  string              $ida      IDA code (3 characters)
     * @param  CarbonImmutable|null $datetime Optional datetime (defaults to now)
     * @param  int|null             $sequence Optional sequence number (0-99999)
     * @return string              18-character FolioID
     */
    public function generate(
        string $ida,
        ?CarbonImmutable $datetime = null,
        ?int $sequence = null,
    ): string {
        $datetime = $datetime ?? CarbonImmutable::now(config('alize.timezone'));
        $sequence = $sequence ?? $this->nextSequence();

        // Year is last 2 digits (AA format means YY)
        $year = $datetime->format('y');
        $monthDayHourMin = $datetime->format('mdHi');

        return sprintf(
            '%s%s%s%05d',
            $ida,
            $year,
            $monthDayHourMin,
            $sequence,
        );
    }

    /**
     * Validates FolioID format.
     *
     * @param  string $folioId FolioID to validate
     * @return bool   True if valid
     */
    public function validate(string $folioId): bool
    {
        // Must be exactly 18 characters
        if (strlen($folioId) !== 18) {
            return false;
        }

        // IDA (3 chars) + YY (2 digits) + MMDDhhmm (8 digits) + Sequence (5 digits)
        return preg_match('/^[A-Z0-9]{3}\d{10}\d{5}$/', $folioId) === 1;
    }

    /**
     * Extracts IDA from FolioID.
     *
     * @param  string $folioId FolioID
     * @return string IDA code
     */
    public function extractIda(string $folioId): string
    {
        return substr($folioId, 0, 3);
    }

    /**
     * Extracts timestamp from FolioID.
     *
     * @param  string          $folioId FolioID
     * @return CarbonImmutable Timestamp
     */
    public function extractTimestamp(string $folioId): CarbonImmutable
    {
        $year = substr($folioId, 3, 2);
        $monthDayHourMin = substr($folioId, 5, 8);

        $fullYear = '20' . $year;
        $timestampStr = $fullYear . $monthDayHourMin;

        $dateTime = CarbonImmutable::createFromFormat(
            'YmdHi',
            $timestampStr,
            config('alize.timezone'),
        );

        if (!$dateTime instanceof CarbonImmutable) {
            throw new \InvalidArgumentException('Invalid FolioID timestamp.');
        }

        return $dateTime;
    }

    /**
     * Extracts sequence number from FolioID.
     *
     * @param  string $folioId FolioID
     * @return int    Sequence number
     */
    public function extractSequence(string $folioId): int
    {
        return (int) substr($folioId, 13, 5);
    }

    /**
     * Gets next sequence number for current hour.
     *
     * @return int Sequence number (0-99999)
     */
    private function nextSequence(): int
    {
        // In production, this should query database for max sequence this hour
        // For now, return random to avoid collisions
        return random_int(1, 99999);
    }
}
