<?php

/**
 * Bootstrap hooks for optional business-day integrations.
 *
 * This file is safe to autoload even when optional third-party packages are not installed.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\\Classes
 * @author  HELA Development Team
 * @license MIT
 */

use Carbon\Carbon;
use Carbon\CarbonImmutable;

Carbon::setLocale('es');
date_default_timezone_set('America/Mexico_City');

if (class_exists('Cmixin\\BusinessDay')) {
    /** @var callable-string $enable */
    $enable = 'Cmixin\\BusinessDay::enable';

    if (is_callable($enable)) {
        $enable(Carbon::class);
        $enable(CarbonImmutable::class);
    }
}

if (class_exists('Yasumi\\Yasumi') && method_exists(Carbon::class, 'setHolidays') && method_exists(Carbon::class, 'setHolidaysRegion')) {
    $currentYear = (int) date('Y');
    $years = [$currentYear - 1, $currentYear, $currentYear + 1];
    $holidays = [];

    foreach ($years as $year) {
        try {
            /** @var callable-string $create */
            $create = 'Yasumi\\Yasumi::create';
            $mexicoHolidays = is_callable($create) ? $create('Mexico', $year) : [];
            foreach ($mexicoHolidays as $holiday) {
                $holidays[] = $holiday->format('Y-m-d');
            }
        } catch (Exception $e) {
            continue;
        }
    }

    if ($holidays !== []) {
        /** @var callable-string $setHolidays */
        $setHolidays = 'Carbon\\Carbon::setHolidays';
        /** @var callable-string $setHolidaysRegion */
        $setHolidaysRegion = 'Carbon\\Carbon::setHolidaysRegion';

        if (is_callable($setHolidays)) {
            $setHolidays('mx-yasumi', array_values(array_unique($holidays)));
        }

        if (is_callable($setHolidaysRegion)) {
            $setHolidaysRegion('mx-yasumi');
        }
    }
}
