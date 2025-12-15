<?php

/**
 * Base XML Message Parser.
 *
 * Provides common XML parsing functionality for NUMLEX messages.
 * Extracts standard fields from NPCData structure.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Xml
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Xml;

use DOMDocument;
use DOMXPath;
use Ometra\HelaAlize\Enums\MessageType;

abstract class MessageParser
{
    protected DOMDocument $doc;

    protected DOMXPath $xpath;

    /**
     * Parses XML message.
     *
     * @param  string $xml XML content
     * @return array  Parsed data
     */
    abstract public function parse(string $xml): array;

    /**
     * Gets the message type this parser handles.
     *
     * @return MessageType Message type
     */
    abstract public function getMessageType(): MessageType;

    /**
     * Initializes DOM document and XPath.
     *
     * @param  string $xml XML content
     * @return void
     */
    protected function initializeDocument(string $xml): void
    {
        $this->doc = new DOMDocument();
        $this->doc->loadXML($xml);

        $this->xpath = new DOMXPath($this->doc);
        $this->xpath->registerNamespace('np', 'urn:npc:mx:np');
    }

    /**
     * Gets text content of element by XPath.
     *
     * @param  string      $path   XPath expression
     * @param  string|null $default Default value
     * @return string|null Element value
     */
    protected function getValue(string $path, ?string $default = null): ?string
    {
        $node = $this->xpath->query($path)->item(0);

        return $node ? $node->nodeValue : $default;
    }

    /**
     * Gets multiple elements by XPath.
     *
     * @param  string $path XPath expression
     * @return array  Array of element values
     */
    protected function getValues(string $path): array
    {
        $nodes = $this->xpath->query($path);
        $values = [];

        foreach ($nodes as $node) {
            $values[] = $node->nodeValue;
        }

        return $values;
    }

    /**
     * Parses MessageHeader fields.
     *
     * @return array Header data
     */
    protected function parseHeader(): array
    {
        return [
            'trans_timestamp' => $this->getValue('//np:MessageHeader/np:TransTimestamp'),
            'sender' => $this->getValue('//np:MessageHeader/np:Sender'),
            'num_messages' => $this->getValue('//np:MessageHeader/np:NumOfMessages'),
        ];
    }

    /**
     * Parses number ranges from Numbers section.
     *
     * @param  string $basePath Base XPath to Numbers element
     * @return array  Array of number ranges
     */
    protected function parseNumbers(string $basePath): array
    {
        $numbers = [];
        $numberNodes = $this->xpath->query("{$basePath}/np:Numbers/np:Number");

        foreach ($numberNodes as $numberNode) {
            $start = $this->xpath->query('np:StartNum', $numberNode)->item(0)?->nodeValue;
            $end = $this->xpath->query('np:EndNum', $numberNode)->item(0)?->nodeValue;

            if ($start && $end) {
                $numbers[] = [
                    'start' => $start,
                    'end' => $end,
                ];
            }
        }

        return $numbers;
    }
}
