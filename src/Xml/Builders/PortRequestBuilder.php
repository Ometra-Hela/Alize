<?php

/**
 * Port Request Message Builder (1001).
 *
 * Builds XML for portation request message (PortReqMsgType).
 * Used by RIDA to initiate portability process.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Xml\Builders
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Xml\Builders;

use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Xml\MessageBuilder;

class PortRequestBuilder extends MessageBuilder
{
    /**
     * Builds port request message (1001).
     *
     * @param  array{
     *   sender: string,
     *   port_id: string,
     *   folio_id: string,
     *   port_type: string,
     *   subscriber_type: string,
     *   recovery_flag: string,
     *   dida: string,
     *   dcr: string,
     *   rida: string,
     *   rcr: string,
     *   numbers: array<array{start: string, end: string}>,
     *   pin?: string,
     *   req_port_exec_date: string,
     *   subs_req_time: string,
     *   comments?: string,
     *   num_files?: int,
     *   file_names?: array<string>
     * } $data Message data
     * @return string XML string
     */
    public function build(array $data): string
    {
        $this->initializeDocument($data['sender']);

        $portReqMsg = $this->doc->createElement('PortRequestMsg');

        // Required fields
        $this->appendChild($portReqMsg, 'PortType', $data['port_type']);
        $this->appendChild($portReqMsg, 'SubscriberType', $data['subscriber_type']);
        $this->appendChild($portReqMsg, 'RecoveryFlagType', $data['recovery_flag']);
        $this->appendChild($portReqMsg, 'PortID', $data['port_id']);
        $this->appendChild($portReqMsg, 'FolioID', $data['folio_id']);
        $this->appendChild($portReqMsg, 'Timestamp', now()->format('YmdHis'));
        $this->appendChild($portReqMsg, 'SubsReqTime', $data['subs_req_time']);
        $this->appendChild($portReqMsg, 'ReqPortExecDate', $data['req_port_exec_date']);

        // Participants
        $this->appendChild($portReqMsg, 'DIDA', $data['dida']);
        $this->appendChild($portReqMsg, 'DCR', $data['dcr']);
        $this->appendChild($portReqMsg, 'RIDA', $data['rida']);
        $this->appendChild($portReqMsg, 'RCR', $data['rcr']);

        // Numbers
        $totalNumbers = array_sum(array_map(
            fn ($range) => (int) $range['end'] - (int) $range['start'] + 1,
            $data['numbers'],
        ));
        $this->appendChild($portReqMsg, 'TotalPhoneNums', (string) $totalNumbers);
        $this->createNumbersSection($portReqMsg, $data['numbers']);

        // Optional fields
        $this->appendChildIfPresent($portReqMsg, 'Pin', $data['pin'] ?? null);
        $this->appendChildIfPresent($portReqMsg, 'Comments', $data['comments'] ?? null);

        // Attachments
        if (isset($data['num_files']) && $data['num_files'] > 0) {
            $this->appendChild($portReqMsg, 'NumOfFiles', (string) $data['num_files']);

            $attachedFiles = $this->doc->createElement('AttachedFiles');
            foreach (($data['file_names'] ?? []) as $fileName) {
                $this->appendChild($attachedFiles, 'FileName', $fileName);
            }
            $portReqMsg->appendChild($attachedFiles);
        }

        $this->npcMessage->appendChild($portReqMsg);

        return $this->toXml();
    }

    /**
     * Gets message type.
     *
     * @return MessageType
     */
    public function getMessageType(): MessageType
    {
        return MessageType::PORT_REQUEST;
    }
}
