<?php

/**
 * Inbound Message Handler Contract.
 *
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Orchestration\Handlers
 * @author  HELA Development Team
 * @license MIT
 */

declare(strict_types=1);

namespace Ometra\HelaAlize\Orchestration\Handlers;

use Ometra\HelaAlize\Models\NpcMessage;

/**
 * Handles a parsed inbound NUMLEX message.
 */
interface InboundMessageHandler
{
    /**
     * Handles the inbound message.
     *
     * @param  NpcMessage $message Received message
     * @return void
     */
    public function handle(NpcMessage $message): void;
}
