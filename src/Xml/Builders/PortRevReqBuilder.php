<?php

namespace Ometra\HelaAlize\Xml\Builders;

use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Xml\MessageBuilder;

class PortRevReqBuilder extends MessageBuilder
{
    /**
     * Builds the complete XML message for 4001.
     *
     * @param  array  $data Message data
     * @return string XML string
     */
    public function build(array $data): string
    {
        $this->initializeDocument($data['dida'], $data['timestamp'] ?? null);

        $msg = $this->doc->createElement('PortRevReqMsg');

        $this->appendChild($msg, 'PortType', $data['port_type']);
        $this->appendChild($msg, 'SubscriberType', $data['subscriber_type']);
        $this->appendChild($msg, 'RecoveryFlagType', $data['recovery_flag'] ?? 'NO');
        $this->appendChild($msg, 'PortID', $data['port_id']);
        $this->appendChild($msg, 'Timestamp', $data['timestamp']->format('YmdHis'));

        $this->appendChild($msg, 'DIDA', $data['dida']);
        if (isset($data['dcr'])) {
            $this->appendChild($msg, 'DCR', $data['dcr']);
        }
        $this->appendChild($msg, 'RIDA', $data['rida']);
        if (isset($data['rcr'])) {
            $this->appendChild($msg, 'RCR', $data['rcr']);
        }

        $this->appendChild($msg, 'TotalPhoneNums', (string) count($data['numbers']));
        $this->createNumbersSection($msg, $data['numbers']);

        if (isset($data['comments'])) {
            $this->appendChild($msg, 'Comments', $data['comments']);
        }

        // 4001 often requires attachments
        if (isset($data['attached_files'])) {
            $this->createAttachmentsSection($msg, $data['attached_files']);
        }

        $this->npcMessage->appendChild($msg);

        return $this->toXml();
    }

    public function getMessageType(): MessageType
    {
        return MessageType::REVERSAL_REQUEST;
    }
}
