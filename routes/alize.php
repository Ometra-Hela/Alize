<?php

/**
 * Portabilities SOAP routes.
 *
 * Defines SOAP endpoint for NUMLEX portability message processing.
 * PHP 8.1+
 *
 * @package Routes
 * @author  HELA Development Team
 * @license MIT
 */

use Illuminate\Support\Facades\Route;
use Ometra\HelaAlize\Http\Controllers\SoapController;

Route::group([
    'prefix' => config('alize.route_prefix', 'alize'),
    'middleware' => 'api',
], function () {
    Route::post('/', [SoapController::class, 'handle']);
});
