<?php

/**
 * Port Response Parser (1004).
 *
 * Parses DIDA response to portation request.
 * Handles acceptance, rejection, and partial rejection.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Xml\Parsers
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Xml\Parsers;

use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Xml\MessageParser;

class PortResponseParser extends MessageParser
{
    /**
     * Parses port response (1004).
     *
     * @param  string $xml XML content
     * @return array{
     *   header: array,
     *   port_id: string,
     *   timestamp: string,
     *   authorization: string,
     *   reason_code: ?string,
     *   reason_text: ?string,
     *   numbers: array,
     *   rejected_numbers: array
     * } Parsed data
     */
    public function parse(string $xml): array
    {
        $this->initializeDocument($xml);

        $basePath = '//np:NPCMessage/np:PortRespMsg';

        $authorization = $this->getValue("{$basePath}/np:AuthorizationInd");

        return [
            'header' => $this->parseHeader(),
            'port_id' => $this->getValue("{$basePath}/np:PortID"),
            'timestamp' => $this->getValue("{$basePath}/np:Timestamp"),
            'authorization' => $authorization,
            'reason_code' => $this->getValue("{$basePath}/np:ReasonCode"),
            'reason_text' => $this->getValue("{$basePath}/np:ReasonText"),
            'numbers' => $this->parseNumbers($basePath),
            'rejected_numbers' => $this->parseRejectedNumbers($basePath),
            'status' => $this->determineStatus($authorization),
        ];
    }

    /**
     * Parses rejected numbers.
     *
     * @param  string $basePath Base XPath
     * @return array  Rejected numbers with reasons
     */
    private function parseRejectedNumbers(string $basePath): array
    {
        $rejected = [];
        $rejectedNodes = $this->xpath->query("{$basePath}/np:RejectedNumbers/np:RejectedNumber");

        foreach ($rejectedNodes as $node) {
            $number = $this->xpath->query('np:Number', $node)->item(0)?->nodeValue;
            $reason = $this->xpath->query('np:ReasonCode', $node)->item(0)?->nodeValue;

            if ($number) {
                $rejected[] = [
                    'number' => $number,
                    'reason_code' => $reason,
                ];
            }
        }

        return $rejected;
    }

    /**
     * Determines response status.
     *
     * @param  string|null $authorization Authorization indicator
     * @return string      Status (ACCEPT/REJECT/PARTIAL_REJECT)
     */
    private function determineStatus(?string $authorization): string
    {
        if ($authorization === 'YES' || $authorization === 'Y') {
            return 'ACCEPT';
        }

        if ($authorization === 'NO' || $authorization === 'N') {
            return 'REJECT';
        }

        return 'PARTIAL_REJECT';
    }

    /**
     * Gets message type.
     *
     * @return MessageType
     */
    public function getMessageType(): MessageType
    {
        return MessageType::PORT_RESPONSE;
    }
}
