<?php

namespace Ometra\HelaAlize\Xml\Builders;

use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Xml\MessageBuilder;

class IndividualPortValidationBuilder extends MessageBuilder
{
    public function build(array $data): string
    {
        // 1201 sent by ABD to DIDA. RIDA doesn't send 1201.
        // But again, for completeness...

        $this->initializeDocument($data['sender'] ?? 'ABD', $data['timestamp'] ?? null);

        $msg = $this->doc->createElement('IndividualPortValidationMsg');

        $this->appendChild($msg, 'PortID', $data['port_id']);
        $this->appendChild($msg, 'Timestamp', $data['timestamp']->format('YmdHis'));

        $this->appendChild($msg, 'DIDA', $data['dida']);
        $this->appendChild($msg, 'RIDA', $data['rida']);

        $this->appendChild($msg, 'TotalPhoneNums', (string) count($data['numbers']));
        $this->createNumbersSection($msg, $data['numbers']);

        if (isset($data['attached_files'])) {
            $this->createAttachmentsSection($msg, $data['attached_files']);
        }

        $this->npcMessage->appendChild($msg);

        return $this->toXml();
    }

    public function getMessageType(): MessageType
    {
        return MessageType::INDIVIDUAL_VALIDATION_REQUEST;
    }
}
