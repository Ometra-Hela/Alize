<?php

/**
 * Schedule Port Message Builder (1006).
 *
 * Builds XML for port scheduling message (SchedulePortMsgType).
 * Used by RIDA to propose execution date/time.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Xml\Builders
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Xml\Builders;

use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Xml\MessageBuilder;

class SchedulePortBuilder extends MessageBuilder
{
    /**
     * Builds schedule port message (1006).
     *
     * @param  array{
     *   sender: string,
     *   port_id: string,
     *   port_type: string,
     *   subscriber_type: string,
     *   recovery_flag: string,
     *   dida: string,
     *   dcr: string,
     *   rida: string,
     *   rcr: string,
     *   numbers: array<array{start: string, end: string}>,
     *   port_exec_date: string,
     *   req_port_exec_date: string,
     *   comments?: string
     * } $data Message data
     * @return string XML string
     */
    public function build(array $data): string
    {
        $this->initializeDocument($data['sender']);

        $scheduleMsg = $this->doc->createElement('SchedulePortMsg');

        // Required fields
        $this->appendChild($scheduleMsg, 'PortType', $data['port_type']);
        $this->appendChild($scheduleMsg, 'SubscriberType', $data['subscriber_type']);
        $this->appendChild($scheduleMsg, 'RecoveryFlagType', $data['recovery_flag']);
        $this->appendChild($scheduleMsg, 'PortID', $data['port_id']);
        $this->appendChild($scheduleMsg, 'Timestamp', now()->format('YmdHis'));

        // Participants
        $this->appendChild($scheduleMsg, 'DIDA', $data['dida']);
        $this->appendChild($scheduleMsg, 'DCR', $data['dcr']);
        $this->appendChild($scheduleMsg, 'RIDA', $data['rida']);
        $this->appendChild($scheduleMsg, 'RCR', $data['rcr']);

        // Numbers
        $totalNumbers = array_sum(array_map(
            fn ($range) => (int) $range['end'] - (int) $range['start'] + 1,
            $data['numbers'],
        ));
        $this->appendChild($scheduleMsg, 'TotalPhoneNums', (string) $totalNumbers);
        $this->createNumbersSection($scheduleMsg, $data['numbers']);

        // Execution dates
        $this->appendChild($scheduleMsg, 'PortExecDate', $data['port_exec_date']);
        $this->appendChild($scheduleMsg, 'ReqPortExecDate', $data['req_port_exec_date']);

        // Optional
        $this->appendChildIfPresent($scheduleMsg, 'Comments', $data['comments'] ?? null);

        $this->npcMessage->appendChild($scheduleMsg);

        return $this->toXml();
    }

    /**
     * Gets message type.
     *
     * @return MessageType
     */
    public function getMessageType(): MessageType
    {
        return MessageType::SCHEDULE_PORT_REQUEST;
    }
}
