<?php

/**
 * Bootstrap file for Portabilities module - initializes Business-Day and Yasumi without ServiceProviders.
 *
 * This file is automatically loaded by Composer autoload configuration.
 * Sets up Carbon business day functionality and Mexican holidays using Yasumi.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\\Classes
 * @author  HELA Development Team
 * @license MIT
 */

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Cmixin\BusinessDay;
use Yasumi\Yasumi;

// Initialize Business-Day mixin for Carbon
BusinessDay::enable(Carbon::class);
BusinessDay::enable(CarbonImmutable::class);

// Set locale and timezone for Mexico
Carbon::setLocale('es');
date_default_timezone_set('America/Mexico_City');

// Try to set holidays region first
try {
    Carbon::setHolidaysRegion('mx-national');
} catch (Exception $e) {
    // If no mx-national region exists, create holidays using Yasumi
    $currentYear = (int) date('Y');
    $years = [$currentYear - 1, $currentYear, $currentYear + 1];
    $holidays = [];

    foreach ($years as $year) {
        try {
            $mexicoHolidays = Yasumi::create('Mexico', $year);
            foreach ($mexicoHolidays as $holiday) {
                $holidays[] = $holiday->format('Y-m-d');
            }
        } catch (Exception $yasumiException) {
            // If Yasumi fails, continue without holidays for this year
            continue;
        }
    }

    if (!empty($holidays)) {
        Carbon::setHolidays('mx-yasumi', array_unique($holidays));
        Carbon::setHolidaysRegion('mx-yasumi');
    }
}
