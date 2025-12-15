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
use DOMNode;
use DOMXPath;
use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Exceptions\NumlexValidationException;

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
        $nodes = $this->xpath->query($path);
        if ($nodes === false) {
            return $default;
        }

        $node = $nodes->item(0);
        if (!$node instanceof DOMNode) {
            return $default;
        }

        return $node->nodeValue ?? $default;
    }

    /**
     * Gets a required element value by XPath.
     *
     * @param  string $path XPath expression
     * @return string
     * @throws NumlexValidationException When the value is missing or empty
     */
    protected function getRequiredValue(string $path): string
    {
        $value = $this->getValue($path);

        if ($value === null || $value === '') {
            throw new NumlexValidationException("Missing required XML value for XPath: {$path}");
        }

        return $value;
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
        if ($nodes === false) {
            return [];
        }

        $values = [];

        foreach ($nodes as $node) {
            if ($node instanceof DOMNode && $node->nodeValue !== null) {
                $values[] = $node->nodeValue;
            }
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
        if ($numberNodes === false) {
            return [];
        }

        foreach ($numberNodes as $numberNode) {
            if (!$numberNode instanceof DOMNode) {
                continue;
            }

            $startNodes = $this->xpath->query('np:StartNum', $numberNode);
            $endNodes = $this->xpath->query('np:EndNum', $numberNode);

            $startNode = $startNodes !== false ? $startNodes->item(0) : null;
            $endNode = $endNodes !== false ? $endNodes->item(0) : null;

            $start = $startNode instanceof DOMNode ? $startNode->nodeValue : null;
            $end = $endNode instanceof DOMNode ? $endNode->nodeValue : null;

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
