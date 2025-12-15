<?php

/**
 * Inbound Message Dispatcher.
 *
 * Routes incoming NUMLEX messages to appropriate handlers based on message type.
 * Orchestrates the processing of SOAP messages received from ABD.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Orchestration
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Orchestration;

use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Models\NpcMessage;
use Ometra\HelaAlize\Orchestration\Handlers\PortRequestAckHandler;
use Ometra\HelaAlize\Orchestration\Handlers\PortResponseHandler;
use Ometra\HelaAlize\Orchestration\Handlers\ReadyToScheduleHandler;
use Ometra\HelaAlize\Orchestration\Handlers\ScheduleNotificationHandler;

class MessageDispatcher
{
    /**
     * Dispatches message to appropriate handler.
     *
     * @param  NpcMessage $message Received message
     * @return void
     */
    public function dispatch(NpcMessage $message): void
    {
        $handler = $this->getHandler($message->type_code);

        if ($handler === null) {
            \Log::warning('No handler for message type', [
                'type' => $message->type_code->value,
                'port_id' => $message->port_id,
            ]);

            return;
        }

        try {
            $handler->handle($message);
        } catch (\Exception $e) {
            \Log::error('Message handler failed', [
                'type' => $message->type_code->value,
                'port_id' => $message->port_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Gets handler for message type.
     *
     * @param  MessageType $type Message type
     * @return object|null Handler instance
     */
    private function getHandler(MessageType $type): ?object
    {
        return match ($type) {
            MessageType::PORT_REQUEST_ACK => new PortRequestAckHandler(),
            MessageType::PORT_RESPONSE => new PortResponseHandler(),
            MessageType::READY_TO_SCHEDULE => new ReadyToScheduleHandler(),
            MessageType::SCHEDULE_PORT_NOTIFICATION => new ScheduleNotificationHandler(),
            // Add more handlers as needed
            default => null,
        };
    }
}
