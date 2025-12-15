<?php

namespace Ometra\HelaAlize\Xml\Builders;

use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Xml\MessageBuilder;

class IndPortValRespBuilder extends MessageBuilder
{
    public function build(array $data): string
    {
        // 1202 sent by DIDA to ABD

        $this->initializeDocument($data['dida'], $data['timestamp'] ?? null);

        $msg = $this->doc->createElement('IndPortValRespMsg');

        $this->appendChild($msg, 'PortID', $data['port_id']);
        $this->appendChild($msg, 'Timestamp', $data['timestamp']->format('YmdHis'));

        $this->appendChild($msg, 'DIDA', $data['dida']);
        $this->appendChild($msg, 'RIDA', $data['rida']);

        $this->appendChild($msg, 'ResultCode', $data['result_code']);
        if (isset($data['result_description'])) {
            $this->appendChild($msg, 'ResultDescription', $data['result_description']);
        }

        $this->npcMessage->appendChild($msg);

        return $this->toXml();
    }

    public function getMessageType(): MessageType
    {
        return MessageType::INDIVIDUAL_VALIDATION_RESPONSE;
    }
}
