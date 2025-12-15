<?php

/**
 * Ready to Schedule Parser (1005).
 *
 * Parses ABD notification that portability is ready to be scheduled.
 * Extracts PortID and timing information.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Xml\Parsers
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Xml\Parsers;

use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Xml\MessageParser;

class ReadyToScheduleParser extends MessageParser
{
    /**
     * Parses ready to schedule notification (1005).
     *
     * @param  string $xml XML content
     * @return array{
     *   header: array,
     *   port_id: string,
     *   timestamp: string,
     *   numbers: array
     * } Parsed data
     */
    public function parse(string $xml): array
    {
        $this->initializeDocument($xml);

        $basePath = '//np:NPCMessage/np:ReadyToScheduleMsg';

        return [
            'header' => $this->parseHeader(),
            'port_id' => $this->getValue("{$basePath}/np:PortID"),
            'timestamp' => $this->getValue("{$basePath}/np:Timestamp"),
            'port_type' => $this->getValue("{$basePath}/np:PortType"),
            'numbers' => $this->parseNumbers($basePath),
        ];
    }

    /**
     * Gets message type.
     *
     * @return MessageType
     */
    public function getMessageType(): MessageType
    {
        return MessageType::READY_TO_SCHEDULE;
    }
}
