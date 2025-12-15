<?php

namespace Ometra\HelaAlize\Xml\Builders;

use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Xml\MessageBuilder;

class PortRevRejectBuilder extends MessageBuilder
{
    public function build(array $data): string
    {
        $this->initializeDocument($data['sender'] ?? 'ABD', $data['timestamp'] ?? null);

        $msg = $this->doc->createElement('RejectMsg'); // 4005 uses generic RejectMsg type? Let's assume so or check doc.
        // portability-details.md says: 4005 | Rechazo de Reversión | RejectMsgType
        // So yes.

        $this->appendChild($msg, 'PortType', $data['port_type']);
        $this->appendChild($msg, 'SubscriberType', $data['subscriber_type']);
        $this->appendChild($msg, 'RecoveryFlagType', $data['recovery_flag'] ?? 'NO');
        $this->appendChild($msg, 'PortID', $data['port_id']);
        $this->appendChild($msg, 'Timestamp', $data['timestamp']->format('YmdHis'));

        $this->appendChild($msg, 'DIDA', $data['dida']);
        $this->appendChild($msg, 'RIDA', $data['rida']);

        $this->appendChild($msg, 'RejectCode', $data['reject_code']);
        $this->appendChild($msg, 'RejectReason', $data['reject_reason']);

        $this->npcMessage->appendChild($msg);

        return $this->toXml();
    }

    public function getMessageType(): MessageType
    {
        return MessageType::REVERSAL_REJECTION;
    }
}
