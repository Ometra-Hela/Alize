<?php

/**
 * Schedule Notification Parser (1007).
 *
 * Parses ABD confirmation that portability has been scheduled.
 * Extracts execution date and final scheduling details.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Xml\Parsers
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Xml\Parsers;

use Carbon\CarbonImmutable;
use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Xml\MessageParser;

class ScheduleNotificationParser extends MessageParser
{
    /**
     * Parses schedule notification (1007).
     *
     * @param  string $xml XML content
     * @return array{
     *   header: array,
     *   port_id: string,
     *   timestamp: string,
     *   port_exec_date: string,
     *   req_port_exec_date: string,
     *   numbers: array
     * } Parsed data
     */
    public function parse(string $xml): array
    {
        $this->initializeDocument($xml);

        $basePath = '//np:NPCMessage/np:SchedulePortMsg';

        return [
            'header' => $this->parseHeader(),
            'port_id' => $this->getRequiredValue("{$basePath}/np:PortID"),
            'timestamp' => $this->getRequiredValue("{$basePath}/np:Timestamp"),
            'port_exec_date' => $this->getRequiredValue("{$basePath}/np:PortExecDate"),
            'req_port_exec_date' => $this->getRequiredValue("{$basePath}/np:ReqPortExecDate"),
            'port_type' => $this->getValue("{$basePath}/np:PortType"),
            'numbers' => $this->parseNumbers($basePath),
        ];
    }

    /**
     * Gets execution date as Carbon instance.
     *
     * @param  array $data Parsed data
     * @return CarbonImmutable|null
     */
    public function getExecutionDate(array $data): ?CarbonImmutable
    {
        if (!isset($data['port_exec_date'])) {
            return null;
        }

        return CarbonImmutable::createFromFormat(
            'YmdHis',
            $data['port_exec_date'],
            config('alize.timezone'),
        );
    }

    /**
     * Gets message type.
     *
     * @return MessageType
     */
    public function getMessageType(): MessageType
    {
        return MessageType::SCHEDULE_PORT_NOTIFICATION;
    }
}
