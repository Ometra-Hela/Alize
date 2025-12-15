<?php

/**
 * Cancellation Request Message Builder (3001).
 *
 * Builds XML for port cancellation request (PortCancelReqMsgType).
 * Used by RIDA to cancel an ongoing portability process.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Xml\Builders
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Xml\Builders;

use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Xml\MessageBuilder;

class CancellationRequestBuilder extends MessageBuilder
{
    /**
     * Builds cancellation request message (3001).
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
     *   comments?: string
     * } $data Message data
     * @return string XML string
     */
    public function build(array $data): string
    {
        $this->initializeDocument($data['sender']);

        $cancelMsg = $this->doc->createElement('PortCancelReqMsg');

        // Required fields
        $this->appendChild($cancelMsg, 'PortType', $data['port_type']);
        $this->appendChild($cancelMsg, 'SubscriberType', $data['subscriber_type']);
        $this->appendChild($cancelMsg, 'RecoveryFlagType', $data['recovery_flag']);
        $this->appendChild($cancelMsg, 'PortID', $data['port_id']);
        $this->appendChild($cancelMsg, 'Timestamp', now()->format('YmdHis'));

        // Participants
        $this->appendChild($cancelMsg, 'DIDA', $data['dida']);
        $this->appendChild($cancelMsg, 'DCR', $data['dcr']);
        $this->appendChild($cancelMsg, 'RIDA', $data['rida']);
        $this->appendChild($cancelMsg, 'RCR', $data['rcr']);

        // Numbers
        $totalNumbers = array_sum(array_map(
            fn ($range) => (int) $range['end'] - (int) $range['start'] + 1,
            $data['numbers'],
        ));
        $this->appendChild($cancelMsg, 'TotalPhoneNums', (string) $totalNumbers);
        $this->createNumbersSection($cancelMsg, $data['numbers']);

        // Optional
        $this->appendChildIfPresent($cancelMsg, 'Comments', $data['comments'] ?? null);

        $this->npcMessage->appendChild($cancelMsg);

        return $this->toXml();
    }

    /**
     * Gets message type.
     *
     * @return MessageType
     */
    public function getMessageType(): MessageType
    {
        return MessageType::CANCELLATION_REQUEST;
    }
}
