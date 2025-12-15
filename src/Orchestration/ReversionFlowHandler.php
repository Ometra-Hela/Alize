<?php

/**
 * Reversion Flow Handler.
 *
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Orchestration
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Orchestration;

use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Models\Portability;
use Ometra\HelaAlize\Models\PortabilityNumber;
use Ometra\HelaAlize\Soap\NumlexSoapClient;
use Ometra\HelaAlize\Xml\Builders\PortRevReqBuilder;

/**
 * Handles Reversion flows.
 */
class ReversionFlowHandler
{
    public function __construct(
        protected NumlexSoapClient $soapClient
    ) {
    }

    /**
     * Request Reversion (4001).
     *
     * @param Portability $portability
     * @param string|null $reason
     * @return array
     */
    public function requestReversion(Portability $portability, ?string $reason = null): array
    {
        if (!is_string($portability->port_id) || $portability->port_id === '') {
            throw new \InvalidArgumentException('Portability port_id is missing.');
        }

        if (!is_string($portability->port_type) || $portability->port_type === '') {
            throw new \InvalidArgumentException('Portability port_type is missing.');
        }

        if (!is_string($portability->subscriber_type) || $portability->subscriber_type === '') {
            throw new \InvalidArgumentException('Portability subscriber_type is missing.');
        }

        if (!is_string($portability->dida) || $portability->dida === '') {
            throw new \InvalidArgumentException('Portability dida is missing.');
        }

        if (!is_string($portability->rida) || $portability->rida === '') {
            throw new \InvalidArgumentException('Portability rida is missing.');
        }

        $numbers = [];
        foreach ($portability->numbers()->get() as $number) {
            if (!$number instanceof PortabilityNumber) {
                continue;
            }

            $msisdn = $number->msisdn_ported;
            if (!is_string($msisdn) || $msisdn === '') {
                continue;
            }

            $numbers[] = ['start' => $msisdn, 'end' => $msisdn];
        }

        // 4001 sent by DIDA->ABD usually to reverse a port out?
        // Or RIDA->ABD?
        // "4001 | Solicitud de Reversión | DIDA→ABD"
        // Wait. Only DIDA can request reversion?
        // If we are RIDA, can we request reversion?
        // "4001 ... DIDA->ABD".
        // If we are RIDA, we might RECEIVE a 4004 (Accept) or 4005 (Reject) after DIDA requests it?
        // Or maybe RIDA can initiate?
        // The table in portability-details.md says: 4001 | DIDA->ABD.
        // So as RIDA we probably DON'T initiate 4001. We might initiate 3001 (Cancel) BEFORE it happens.
        // Post-porting reversion is usually DIDA claiming the user back.
        // However, the plan "Implement Core Changes (RIDA focused)" listed "Reversion Flow: 4001".
        // And "Expose methods... requestReversion".
        // If I am strictly RIDA, I might not need this method?
        // But the user asked for full functionality availability.
        // I will implement it assuming we *might* act as DIDA or the doc allows RIDA in some cases (though standard says DIDA).

        $data = [
            'port_type' => $portability->port_type,
            'subscriber_type' => $portability->subscriber_type,
            'recovery_flag' => 'NO', // Default
            'port_id' => $portability->port_id,
            'timestamp' => now(),
            'dida' => $portability->dida,
            'rida' => $portability->rida,
            'dcr' => $portability->dcr,
            'rcr' => $portability->rcr,
            'numbers' => $numbers,
        ];

        if (is_string($reason) && $reason !== '') {
            $data['comments'] = $reason;
        }

        $builder = new PortRevReqBuilder();
        $xml = $builder->build($data);

        // Warning: This sends as if we are the sender.
        // Checks in NumlexSoapClient might fail if we are not the DIDA in config.

        return $this->soapClient->processNPCMsg(
            $xml,
            MessageType::REVERSAL_REQUEST,
            $portability->port_id
        );
    }
}
