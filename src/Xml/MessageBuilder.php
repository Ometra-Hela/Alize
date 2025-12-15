<?php

/**
 * Base message builder for NUMLEX XML messages.
 *
 * Provides common XML building functionality for all message types.
 * Handles namespace declaration, header creation, and structure validation.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Xml
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Xml;

use Carbon\CarbonImmutable;
use DOMDocument;
use DOMElement;
use Ometra\HelaAlize\Enums\MessageType;

abstract class MessageBuilder
{
    protected const NAMESPACE_URI = 'urn:npc:mx:np';

    protected DOMDocument $doc;

    protected DOMElement $root;

    protected DOMElement $npcMessage;

    /**
     * Builds the complete XML message.
     *
     * @param  array  $data Message data
     * @return string XML string
     */
    abstract public function build(array $data): string;

    /**
     * Gets the message type this builder creates.
     *
     * @return MessageType Message type
     */
    abstract public function getMessageType(): MessageType;

    /**
     * Initializes XML document structure.
     *
     * @param  string              $sender          Sender IDA
     * @param  CarbonImmutable|null $transTimestamp Transaction timestamp
     * @return void
     */
    protected function initializeDocument(
        string $sender,
        ?CarbonImmutable $transTimestamp = null,
    ): void {
        $this->doc = new DOMDocument('1.0', 'UTF-8');
        $this->doc->formatOutput = true;

        // Create root NPCData element
        $this->root = $this->doc->createElementNS(self::NAMESPACE_URI, 'NPCData');
        $this->doc->appendChild($this->root);

        // Create MessageHeader
        $this->createMessageHeader(
            $sender,
            $transTimestamp ?? CarbonImmutable::now(config('alize.timezone')),
        );

        // Create NPCMessage container
        $this->npcMessage = $this->doc->createElement('NPCMessage');
        $this->root->appendChild($this->npcMessage);
    }

    /**
     * Creates MessageHeader element.
     *
     * @param  string          $sender          Sender IDA
     * @param  CarbonImmutable $transTimestamp Transaction timestamp
     * @return void
     */
    protected function createMessageHeader(
        string $sender,
        CarbonImmutable $transTimestamp,
    ): void {
        $header = $this->doc->createElement('MessageHeader');

        $this->appendChild(
            $header,
            'TransTimestamp',
            $transTimestamp->format('YmdHis'),
        );

        $this->appendChild($header, 'Sender', $sender);
        $this->appendChild($header, 'NumOfMessages', '1');

        $this->root->appendChild($header);
    }

    /**
     * Appends child element with text content.
     *
     * @param  DOMElement $parent Parent element
     * @param  string     $name   Element name
     * @param  string     $value  Element value
     * @return DOMElement Created element
     */
    protected function appendChild(
        DOMElement $parent,
        string $name,
        string $value,
    ): DOMElement {
        $element = $this->doc->createElement($name, htmlspecialchars($value, ENT_XML1));
        $parent->appendChild($element);

        return $element;
    }

    /**
     * Appends child element if value is not null/empty.
     *
     * @param  DOMElement   $parent Parent element
     * @param  string       $name   Element name
     * @param  string|null  $value  Element value
     * @return DOMElement|null Created element or null
     */
    protected function appendChildIfPresent(
        DOMElement $parent,
        string $name,
        ?string $value,
    ): ?DOMElement {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->appendChild($parent, $name, $value);
    }

    /**
     * Creates Numbers section with number ranges.
     *
     * @param  DOMElement    $parent  Parent element
     * @param  array<array>  $numbers Array of number ranges
     * @return void
     */
    protected function createNumbersSection(
        DOMElement $parent,
        array $numbers,
    ): void {
        $numbersElement = $this->doc->createElement('Numbers');

        foreach ($numbers as $range) {
            $numberElement = $this->doc->createElement('Number');
            $this->appendChild($numberElement, 'StartNum', $range['start']);
            $this->appendChild($numberElement, 'EndNum', $range['end']);
            $numbersElement->appendChild($numberElement);
        }

        $parent->appendChild($numbersElement);
    }

    /**
     * Creates AttachedFiles section.
     *
     * @param  DOMElement    $parent      Parent element
     * @param  array<string> $fileNames   List of file names
     * @return void
     */
    protected function createAttachmentsSection(
        DOMElement $parent,
        array $fileNames,
    ): void {
        if (empty($fileNames)) {
            return;
        }

        $this->appendChild($parent, 'NumOfFiles', (string) count($fileNames));

        $filesElement = $this->doc->createElement('AttachedFiles');

        foreach ($fileNames as $name) {
            $this->appendChild($filesElement, 'FileName', $name);
        }

        $parent->appendChild($filesElement);
    }

    /**
     * Converts document to XML string.
     *
     * @return string XML string
     */
    protected function toXml(): string
    {
        return $this->doc->saveXML();
    }
}
