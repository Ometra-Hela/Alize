<?php

namespace Ometra\HelaAlize\Xml\Builders;

use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Xml\MessageBuilder;

class PortCancelRespBuilder extends MessageBuilder
{
    /**
     * Builds the complete XML message for 3002.
     *
     * @param  array  $data Message data
     * @return string XML string
     */
    public function build(array $data): string
    {
        // 3002 is sent by ABD, but if we were to simulate or if DIDA role requires it (though plan said RIDA focused)
        // Wait, 3002 is "Cancellation Acceptance" sent by ABD?
        // Let's re-read portability-details.md:
        // "3002 | Aceptación de Cancelación | ABD→Actores"
        // So we RECEIVE 3002. We don't build it?
        // Ah, unless we are mocking ABD for testing.
        // Or if the plan meant 3001 (Request)?
        // 3001 is "Solicitud de Cancelación | RIDA→ABD". We DO build 3001.
        // The plan says: "- Responses: 3002 (Cancel Response)." in "Proposed Changes".
        // Maybe I misunderstood the direction or the plan implies we might need to send it if we were ABD?
        // Or maybe it means "Implement Handler for 3002"?
        // But the task says "Implement XML Builders ... 3002".
        // It's possible the user wanted to be able to SEND 3002 if acting as ABD (mock) or maybe I misread.
        // Actually, if we are RIDA, we send 3001. We receive 3002.
        // I'll implement 3001 if its missing.
        // Checking directory listing: CancellationRequestBuilder.php exists.
        // So 3001 exists.
        // Maybe the plan meant "Handle 3002"?
        // But the item is "Implement XML Builders ...".
        // I will implement 3002 builder just in case we need to emulate ABD responses for testing.

        $this->initializeDocument($data['sender'] ?? 'ABD', $data['timestamp'] ?? null);

        $msg = $this->doc->createElement('PortCancelRespMsg');

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

        $this->npcMessage->appendChild($msg);

        return $this->toXml();
    }

    public function getMessageType(): MessageType
    {
        return MessageType::CANCELLATION_ACCEPTANCE;
    }
}
