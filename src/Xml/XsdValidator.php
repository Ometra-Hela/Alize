<?php

/**
 * XSD Schema Validator.
 *
 * Validates NUMLEX XML messages against official XSD schemas.
 * Ensures compliance with ABD specification before sending/after receiving.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Xml
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Xml;

use DOMDocument;
use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Exceptions\NumlexValidationException;

class XsdValidator
{
    private string $xsdPath;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->xsdPath = config('alize.xsd_path');
    }

    /**
     * Validates XML against XSD schema.
     *
     * @param  string      $xml         XML content
     * @param  MessageType $messageType Message type for schema selection
     * @return bool        True if valid
     * @throws \Exception  When validation fails
     */
    public function validate(string $xml, MessageType $messageType): bool
    {
        if (!$this->isXsdAvailable()) {
            \Log::warning('XSD schemas not available, skipping validation');

            return true;
        }

        $schemaFile = $this->getSchemaFile($messageType);

        if (!file_exists($schemaFile)) {
            \Log::warning('XSD schema file not found', [
                'file' => $schemaFile,
                'message_type' => $messageType->value,
            ]);

            return true;
        }

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        libxml_use_internal_errors(true);

        if (!$dom->schemaValidate($schemaFile)) {
            $errors = libxml_get_errors();
            libxml_clear_errors();

            $errorMessages = array_map(
                fn ($error) => trim($error->message),
                $errors,
            );

            throw new NumlexValidationException(
                'XSD validation failed: ' . implode('; ', $errorMessages),
            );
        }

        return true;
    }

    /**
     * Checks if XSD schemas are available.
     *
     * @return bool True if available
     */
    private function isXsdAvailable(): bool
    {
        return is_dir($this->xsdPath);
    }

    /**
     * Gets XSD schema file for message type.
     *
     * @param  MessageType $messageType Message type
     * @return string      Schema file path
     */
    private function getSchemaFile(MessageType $messageType): string
    {
        // Map message types to schema files
        // In production, use actual NUMLEX schema organization
        $schemaMap = [
            MessageType::PORT_REQUEST->value => 'PortRequest.xsd',
            MessageType::PORT_REQUEST_ACK->value => 'PortRequestAck.xsd',
            MessageType::PORT_RESPONSE->value => 'PortResponse.xsd',
            MessageType::SCHEDULE_PORT_REQUEST->value => 'SchedulePort.xsd',
            MessageType::SCHEDULE_PORT_NOTIFICATION->value => 'SchedulePort.xsd',
            MessageType::CANCELLATION_REQUEST->value => 'Cancellation.xsd',
        ];

        $schemaFile = $schemaMap[$messageType->value] ?? 'NPCData.xsd';

        return $this->xsdPath . '/' . $schemaFile;
    }

    /**
     * Validates and throws exception on error.
     *
     * @param  string      $xml         XML content
     * @param  MessageType $messageType Message type
     * @return void
     * @throws \Exception
     */
    public function validateOrFail(string $xml, MessageType $messageType): void
    {
        $this->validate($xml, $messageType);
    }
}
