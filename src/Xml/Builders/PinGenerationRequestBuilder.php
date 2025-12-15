<?php

namespace Ometra\HelaAlize\Xml\Builders;

use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Xml\MessageBuilder;

class PinGenerationRequestBuilder extends MessageBuilder
{
    /**
     * Builds the complete XML message for 2001.
     *
     * @param  array  $data Message data
     * @return string XML string
     */
    public function build(array $data): string
    {
        $this->initializeDocument($data['rida'], $data['timestamp'] ?? null);

        $msg = $this->doc->createElement('PinGenerationRequestMsg');

        $this->appendChild($msg, 'PortType', $data['port_type']);
        // Optional ContactMsisdn if needed, usually same as startNum of first range
        if (isset($data['contact_msisdn'])) {
            $this->appendChild($msg, 'ContactMsisdn', $data['contact_msisdn']);
        }

        $this->appendChild($msg, 'PinType', $data['pin_type'] ?? 'GENERATE');
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

        $this->npcMessage->appendChild($msg);

        return $this->toXml();
    }

    public function getMessageType(): MessageType
    {
        return MessageType::PIN_GENERATION_REQUEST;
    }
}
