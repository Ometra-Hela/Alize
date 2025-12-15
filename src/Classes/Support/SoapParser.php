<?php

/**
 * SOAP message parser for NUMLEX portability messages.
 *
 * Parses SOAP XML messages according to NUMLEX WSDL/XSD specifications.
 * Extracts credentials, message data, and attachments from SOAP envelopes.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Classes\Support
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Classes\Support;

use DOMDocument;
use DOMNode;
use DOMXPath;
use stdClass;

class SoapParser
{
    /**
     * Parses a SOAP XML message and extracts portability data.
     *
     * @param string $soapXml Raw SOAP XML content
     * @return stdClass Parsed message data with properties:
     *                  - userId: string
     *                  - passwordBase64: string
     *                  - xmlMsg: string
     *                  - attachments: array
     *                  - messageId: string|null
     *                  - typeCode: string|null
     *                  - msisdn: string|null
     *                  - portabilityId: string|null
     */
    public function parse(string $soapXml): stdClass
    {
        $dom = new DOMDocument();
        $dom->loadXML($soapXml);
        $xpath = new DOMXPath($dom);

        // Register SOAP namespace
        $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xpath->registerNamespace('np', 'http://www.numlex.com/npc');

        $result = new stdClass();

        // Extract authentication credentials
        $result->userId = $this->extractTextValue(
            xpath: $xpath,
            query: '//np:userId'
        );

        $result->passwordBase64 = $this->extractTextValue(
            xpath: $xpath,
            query: '//np:passwordBase64'
        );

        // Extract XML message content
        $result->xmlMsg = $this->extractTextValue(
            xpath: $xpath,
            query: '//np:xmlMsg'
        );

        // Extract message metadata
        $result->messageId = $this->extractTextValue(
            xpath: $xpath,
            query: '//np:messageId'
        );

        $result->typeCode = $this->extractTextValue(
            xpath: $xpath,
            query: '//np:typeCode'
        );

        $result->msisdn = $this->extractTextValue(
            xpath: $xpath,
            query: '//np:msisdn'
        );

        $result->portabilityId = $this->extractTextValue(
            xpath: $xpath,
            query: '//np:portabilityId'
        );

        // Extract attachments if present
        $result->attachments = $this->extractAttachments($xpath);

        return $result;
    }

    /**
     * Extracts text content from XPath query.
     *
     * @param DOMXPath $xpath XPath object
     * @param string   $query XPath query string
     * @return string|null Extracted text or null if not found
     */
    private function extractTextValue(
        DOMXPath $xpath,
        string $query,
        ?DOMNode $contextNode = null
    ): ?string {
        $nodes = $xpath->query($query, $contextNode);

        if ($nodes === false) {
            return null;
        }

        if ($nodes->length === 0) {
            return null;
        }

        $node = $nodes->item(0);
        if ($node === null || !$node instanceof DOMNode) {
            return null;
        }

        return trim($node->textContent);
    }

    /**
     * Extracts attachments from SOAP message.
     *
     * @param DOMXPath $xpath XPath object for SOAP document
     * @return array Array of attachment data with keys:
     *               - name: string
     *               - mime: string
     *               - size_bytes: int
     *               - content: string (base64 encoded)
     */
    private function extractAttachments(DOMXPath $xpath): array
    {
        /** @var array<int, array{name: string, mime: string, size_bytes: int, content: string}> $attachments */
        $attachments = [];

        // Look for attachment nodes in SOAP message
        $attachmentNodes = $xpath->query('//np:attachment');
        if ($attachmentNodes === false) {
            return $attachments;
        }

        foreach ($attachmentNodes as $node) {
            if (!$node instanceof DOMNode) {
                continue;
            }
            $attachment = [];

            $attachment['name'] = $this->extractTextValue(
                xpath: $xpath,
                query: './np:filename',
                contextNode: $node
            ) ?? 'unknown';

            $attachment['mime'] = $this->extractTextValue(
                xpath: $xpath,
                query: './np:mimeType',
                contextNode: $node
            ) ?? 'application/octet-stream';

            $contentBase64 = $this->extractTextValue(
                xpath: $xpath,
                query: './np:content',
                contextNode: $node
            ) ?? '';

            $attachment['content'] = $contentBase64;
            $decoded = base64_decode($contentBase64, true);
            $attachment['size_bytes'] = $decoded === false ? 0 : strlen($decoded);

            $attachments[] = $attachment;
        }

        return $attachments;
    }
}
