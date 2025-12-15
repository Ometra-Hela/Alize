<?php

/**
 * NIP Flow Handler.
 *
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Orchestration
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Orchestration;

use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Soap\NumlexSoapClient;
use Ometra\HelaAlize\Xml\Builders\PinGenerationRequestBuilder;

/**
 * Handles NIP generation flows.
 */
class NipFlowHandler
{
    public function __construct(
        protected NumlexSoapClient $soapClient
    ) {}

    /**
     * Request NIP generation (2001).
     *
     * @param string $msisdn
     * @param string|null $dida Override DIDA (optional)
     * @return array Response result
     */
    public function requestNip(string $msisdn, ?string $dida = null): array
    {
        // For NIP request, we usually assume a port type of MOBILE and INDIVIDUAL
        // The DIDA might be unknown, but the message requires it.
        // If DIDA is not provided, we might need to lookup or use a placeholder if allowed?
        // Actually portability-details says: 2001 | Solicitud Generación NIP | RIDA/DIDA→ABD
        // ABD forwards to the operator.
        // Assuming we know the DIDA or we are the DIDA/RIDA.
        // Let's assume passed or config default (though config default DIDA is us if we are DIDA, but if we are RIDA requesting NIP from DIDA... wait).
        // RIDA requests NIP?
        // "2001 ... RIDA/DIDA->ABD".
        // Usually RIDA requests it on behalf of user.

        $data = [
            'port_type' => 'MOBILE',
            'dida' => $dida ?? '000', // Needs to be provided. 000 is invalid.
            'rida' => config('alize.ida'),
            'contact_msisdn' => $msisdn,
            'pin_type' => 'GENERATE',
            'port_id' => $this->generatePortId('NIP'), // NIP flows often have their own ID or share PortID strategy?
            // "PortID ... Aplicable a varios mensajes, incluyendo ... 2001"
            'timestamp' => now(),
            'numbers' => [
                ['start' => $msisdn, 'end' => $msisdn]
            ]
        ];

        $builder = new PinGenerationRequestBuilder();
        $xml = $builder->build($data);

        return $this->soapClient->processNPCMsg(
            $xml,
            MessageType::PIN_GENERATION_REQUEST,
            $data['port_id']
        );
    }

    private function generatePortId(string $prefix): string
    {
        // Simple generation for now, should match PortID spec logic
        // IDA + YYYYMMDDhhmmss + nnnn
        return config('alize.ida') . now()->format('YmdHis') . rand(1000, 9999);
    }
}
