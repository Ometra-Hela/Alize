<?php

/**
 * Port Request Acknowledgment Parser (1002).
 *
 * Parses ABD acknowledgment of port request.
 * Extracts status and validation results.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Xml\Parsers
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Xml\Parsers;

use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Xml\MessageParser;

class PortRequestAckParser extends MessageParser
{
    /**
     * Parses port request acknowledgment (1002).
     *
     * @param  string $xml XML content
     * @return array{
     *   header: array,
     *   port_id: string,
     *   timestamp: string,
     *   ack_status: string,
     *   error_code: ?string,
     *   error_message: ?string
     * } Parsed data
     */
    public function parse(string $xml): array
    {
        $this->initializeDocument($xml);

        $basePath = '//np:NPCMessage/np:PortRequestAckMsg';

        return [
            'header' => $this->parseHeader(),
            'port_id' => $this->getValue("{$basePath}/np:PortID"),
            'timestamp' => $this->getValue("{$basePath}/np:Timestamp"),
            'ack_status' => $this->determineAckStatus($basePath),
            'error_code' => $this->getValue("{$basePath}/np:ErrorCode"),
            'error_message' => $this->getValue("{$basePath}/np:ErrorMessage"),
        ];
    }

    /**
     * Determines acknowledgment status from XML.
     *
     * @param  string $basePath Base XPath
     * @return string Status (SUCCESS/ERROR)
     */
    private function determineAckStatus(string $basePath): string
    {
        $errorCode = $this->getValue("{$basePath}/np:ErrorCode");

        // If no error code or error code is success indicator
        if (!$errorCode || $errorCode === '0' || $errorCode === '000') {
            return 'SUCCESS';
        }

        return 'ERROR';
    }

    /**
     * Gets message type.
     *
     * @return MessageType
     */
    public function getMessageType(): MessageType
    {
        return MessageType::PORT_REQUEST_ACK;
    }
}
