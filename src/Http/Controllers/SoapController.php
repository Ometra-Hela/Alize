<?php

/**
 * SOAP controller for NUMLEX portability messages.
 *
 * Thin controller that delegates SOAP message processing to ProcessNpcMsgAction.
 * Handles incoming SOAP requests from NUMLEX system.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Http\Controllers
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Http\Controllers;

use Illuminate\Http\Response;
use Ometra\HelaAlize\Classes\Soap\ProcessNpcMsgAction;
use Ometra\HelaAlize\Http\Requests\ProcessNpcMsgRequest;

class SoapController
{
    /**
     * Handles incoming SOAP messages from NUMLEX.
     *
     * @param  ProcessNpcMsgRequest $request The validated HTTP request containing SOAP XML
     * @return Response                      Text response with 'éxito' on success
     */
    public function handle(ProcessNpcMsgRequest $request): Response
    {
        $action = new ProcessNpcMsgAction();

        return $action->execute($request);
    }
}
