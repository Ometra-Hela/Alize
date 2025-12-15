<?php

namespace Ometra\HelaAlize\Xml\Builders;

use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Xml\MessageBuilder;

class PortRevAcceptBuilder extends MessageBuilder
{
    public function build(array $data): string
    {
        $this->initializeDocument($data['sender'] ?? 'ABD', $data['timestamp'] ?? null);

        $msg = $this->doc->createElement('PortRevAcceptMsg');

        $this->appendChild($msg, 'PortType', $data['port_type']);
        $this->appendChild($msg, 'SubscriberType', $data['subscriber_type']);
        $this->appendChild($msg, 'RecoveryFlagType', $data['recovery_flag'] ?? 'NO');
        $this->appendChild($msg, 'PortID', $data['port_id']);
        $this->appendChild($msg, 'Timestamp', $data['timestamp']->format('YmdHis'));
        $this->appendChild($msg, 'RevExecDate', $data['rev_exec_date']->format('YmdHis'));

        $this->appendChild($msg, 'DIDA', $data['dida']);
        $this->appendChild($msg, 'RIDA', $data['rida']);

        $this->appendChild($msg, 'TotalPhoneNums', (string) count($data['numbers']));
        $this->createNumbersSection($msg, $data['numbers']);

        $this->npcMessage->appendChild($msg);

        return $this->toXml();
    }

    public function getMessageType(): MessageType
    {
        return MessageType::REVERSAL_ACCEPTANCE;
    }
}
