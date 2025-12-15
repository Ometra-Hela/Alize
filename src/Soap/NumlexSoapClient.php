<?php

/**
 * NUMLEX SOAP Client.
 *
 * Sends SOAP messages to ABD/NUMLEX endpoint using processNPCMsg method.
 * Handles TLS certificates, Base64 password encoding, and retry logic.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Soap
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Soap;

use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Exceptions\NumlexSoapException;
use Ometra\HelaAlize\Models\NpcMessage;
use SoapClient;
use SoapFault;

class NumlexSoapClient
{
    private SoapClient $client;

    private string $userId;

    private string $passwordBase64;

    /**
     * Constructor.
     *
     * @throws SoapFault
     */
    public function __construct()
    {
        $config = config('alize.soap');

        $this->userId = $config['user_id'];
        $this->passwordBase64 = $config['password_b64'];

        $this->client = new SoapClient(null, [
            'location' => $config['client_endpoint'],
            'uri' => 'urn:npc:mx:np',
            'trace' => true,
            'exceptions' => true,
            'connection_timeout' => $config['timeout'],
            'local_cert' => $config['tls']['cert_path'],
            'local_pk' => $config['tls']['key_path'],
            'cafile' => $config['tls']['ca_path'],
            'verify_peer' => true,
            'verify_peer_name' => true,
        ]);
    }

    /**
     * Sends message via SOAP processNPCMsg method.
     *
     * @param  string               $xmlMessage XML message to send
     * @param  MessageType          $messageType Type of message
     * @param  string               $portId Associated port ID
     * @return array{success: bool, response: string, error: ?string}
     */
    public function processNPCMsg(
        string $xmlMessage,
        MessageType $messageType,
        string $portId,
    ): array {
        try {
            // Validate XML against XSD schema
            $validator = new \Ometra\HelaAlize\Xml\XsdValidator();
            $validator->validate($xmlMessage, $messageType);

            // Call SOAP method with Base64-encoded password
            $response = $this->client->__soapCall('processNPCMsg', [
                'userID' => $this->userId,
                'password' => $this->passwordBase64,
                'xmlMsg' => $xmlMessage,
            ]);

            // Store successful message
            NpcMessage::create([
                'port_id' => $portId,
                'message_id' => $messageType->value,
                'direction' => 'OUT',
                'type_code' => $messageType,
                'sender' => config('alize.ida'),
                'raw_xml' => $xmlMessage,
                'sent_at' => now(),
                'ack_status' => 'SUCCESS',
                'ack_text' => $response,
                'idempotency_key' => $this->generateIdempotencyKey($portId, $messageType, $xmlMessage),
            ]);

            return [
                'success' => true,
                'response' => $response,
                'error' => null,
            ];
        } catch (SoapFault $e) {
            // Store failed message
            NpcMessage::create([
                'port_id' => $portId,
                'message_id' => $messageType->value,
                'direction' => 'OUT',
                'type_code' => $messageType,
                'sender' => config('alize.ida'),
                'raw_xml' => $xmlMessage,
                'sent_at' => now(),
                'ack_status' => 'ERROR',
                'ack_text' => $e->getMessage(),
                'retry_count' => 0,
                'idempotency_key' => $this->generateIdempotencyKey($portId, $messageType, $xmlMessage),
            ]);

            return [
                'success' => false,
                'response' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sends message with retry logic.
     *
     * @param  string      $xmlMessage XML message
     * @param  MessageType $messageType Message type
     * @param  string      $portId Port ID
     * @param  int         $maxRetries Maximum retry attempts
     * @return array       Result array
     */
    public function sendWithRetry(
        string $xmlMessage,
        MessageType $messageType,
        string $portId,
        int $maxRetries = 3,
    ): array {
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $result = $this->processNPCMsg($xmlMessage, $messageType, $portId);

            if ($result['success']) {
                return $result;
            }

            $attempt++;

            if ($attempt < $maxRetries) {
                // Exponential backoff: 1s, 2s, 4s
                sleep(2 ** ($attempt - 1));
            }
        }

        return $result;
    }

    /**
     * Generates idempotency key for deduplication.
     *
     * @param  string      $portId Port ID
     * @param  MessageType $messageType Message type
     * @param  string      $xmlMessage XML content
     * @return string      Idempotency key
     */
    private function generateIdempotencyKey(
        string $portId,
        MessageType $messageType,
        string $xmlMessage,
    ): string {
        return hash('sha256', $portId . $messageType->value . $xmlMessage);
    }
}
