<?php

/**
 * Mexican business calendar with working window management.
 *
 * Handles business day calculations and working hours enforcement (11:00-17:00 MX)
 * using Business-Day mixin and Mexican holidays from Yasumi.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\\Classes\Calendar
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Classes\Calendar;

use Carbon\CarbonImmutable;

class BusinessCalendarMX
{
    /**
     * Checks if a given date is a business day in Mexico.
     *
     * @param CarbonImmutable $date The date to check
     * @return bool True if business day, false otherwise
     */
    public function isBusinessDay(CarbonImmutable $date): bool
    {
        return $date->isBusinessDay();
    }

    /**
     * Adds business days to a given date.
     *
     * @param CarbonImmutable $date     The starting date
     * @param int             $days     Number of business days to add
     * @return CarbonImmutable The resulting date
     */
    public function addBusinessDays(
        CarbonImmutable $date,
        int $days
    ): CarbonImmutable {
        return $date->addBusinessDays($days);
    }

    /**
     * Clamps a datetime to the working window (11:00-17:00 MX).
     *
     * If outside working hours or not a business day, jumps to next business day at 11:00.
     *
     * @param CarbonImmutable $datetime The datetime to clamp
     * @return CarbonImmutable The clamped datetime
     */
    public function clampToWorkingWindow(CarbonImmutable $datetime): CarbonImmutable
    {
        $config = config('alize.business_hours');
        $startTime = $config['start'];
        $endTime = $config['end'];

        // Convert to Mexico timezone
        $datetime = $datetime->setTimezone(config('alize.timezone'));

        // If not a business day, move to next business day at start time
        if (!$this->isBusinessDay($datetime)) {
            return $this->getNextBusinessDayAt($datetime, $startTime);
        }

        // Extract time components
        $timeStr = $datetime->format('H:i');

        // If before start time, set to start time same day
        if ($timeStr < $startTime) {
            return $datetime->setTimeFromTimeString($startTime);
        }

        // If after end time, move to next business day at start time
        if ($timeStr >= $endTime) {
            return $this->getNextBusinessDayAt($datetime, $startTime);
        }

        // Within working hours, return as-is
        return $datetime;
    }

    /**
     * Adds business hours to a starting datetime, respecting working window and business days.
     *
     * @param CarbonImmutable $start The starting datetime
     * @param int             $hours Number of business hours to add
     * @return CarbonImmutable The resulting datetime
     */
    public function addBusinessHours(
        CarbonImmutable $start,
        int $hours
    ): CarbonImmutable {
        $config = config('alize.business_hours');
        $startTime = $config['start'];
        $endTime = $config['end'];

        // Start from a valid working time
        $current = $this->clampToWorkingWindow($start);
        $hoursToAdd = $hours;

        while ($hoursToAdd > 0) {
            // Calculate available hours in current day
            $dayStart = $current->setTimeFromTimeString($startTime);
            $dayEnd = $current->setTimeFromTimeString($endTime);
            $availableHours = $dayEnd->diffInHours($current);

            // If we can fit remaining hours in current day
            if ($hoursToAdd <= $availableHours) {
                return $current->addHours($hoursToAdd);
            }

            // Move to next business day and subtract used hours
            $hoursToAdd -= $availableHours;
            $current = $this->getNextBusinessDayAt($current, $startTime);
        }

        return $current;
    }

    /**
     * Gets the next business day at a specific time.
     *
     * @param CarbonImmutable $date The reference date
     * @param string          $time Time string (e.g., '11:00')
     * @return CarbonImmutable Next business day at specified time
     */
    private function getNextBusinessDayAt(
        CarbonImmutable $date,
        string $time
    ): CarbonImmutable {
        $nextDay = $date->addDay();

        while (!$this->isBusinessDay($nextDay)) {
            $nextDay = $nextDay->addDay();
        }

        return $nextDay->setTimeFromTimeString($time);
    }
}
