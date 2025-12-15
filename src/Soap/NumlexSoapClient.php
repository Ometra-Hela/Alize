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
use Ometra\HelaAlize\Exceptions\IntegrationException;
use Ometra\HelaAlize\Models\NpcMessage;
use Ometra\HelaAlize\Support\CircuitBreaker;
use SoapClient;
use SoapFault;

class NumlexSoapClient
{
    private SoapClient $client;

    private string $userId;

    private string $passwordBase64;

    private CircuitBreaker $circuitBreaker;

    private int $retryDelayMs = 1000;

    /**
     * Constructor.
     *
     * @throws SoapFault
     */
    public function __construct()
    {
        $config = config('alize.soap');

        if (!is_array($config)) {
            throw new IntegrationException('Invalid SOAP configuration (alize.soap).');
        }

        $tls = $config['tls'] ?? null;
        if (!is_array($tls)) {
            throw new IntegrationException('Invalid SOAP TLS configuration (alize.soap.tls).');
        }

        $this->userId = (string) ($config['user_id'] ?? '');
        $this->passwordBase64 = (string) ($config['password_b64'] ?? '');

        if ($this->userId === '' || $this->passwordBase64 === '') {
            throw new IntegrationException('Missing SOAP credentials (alize.soap.user_id / alize.soap.password_b64).');
        }

        $endpoint = (string) ($config['client_endpoint'] ?? '');
        $this->retryDelayMs = (int) ($config['retry_delay_ms'] ?? 1000);

        // Initialize circuit breaker
        $cb = (array) ($config['circuit_breaker'] ?? []);
        $this->circuitBreaker = new CircuitBreaker(
            name: 'soap_' . md5($endpoint),
            failureThreshold: (int) ($cb['failure_threshold'] ?? 5),
            openSeconds: (int) ($cb['open_seconds'] ?? 60),
            halfOpenMaxSuccesses: (int) ($cb['half_open_successes'] ?? 1),
        );

        $this->client = $this->makeSoapClient([
            'location' => $endpoint,
            'uri' => 'urn:npc:mx:np',
            'trace' => true,
            'exceptions' => true,
            'connection_timeout' => (int) ($config['timeout'] ?? 30),
            'local_cert' => (string) ($tls['cert_path'] ?? ''),
            'local_pk' => (string) ($tls['key_path'] ?? ''),
            'cafile' => (string) ($tls['ca_path'] ?? ''),
            'verify_peer' => true,
            'verify_peer_name' => true,
        ]);
    }

    /**
     * SoapClient factory for testability.
     *
     * @param  array<string, mixed> $options SoapClient options
     * @return SoapClient
     */
    protected function makeSoapClient(array $options): SoapClient
    {
        return new SoapClient(null, $options);
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
        if (! $this->circuitBreaker->allowRequest()) {
            return [
                'success' => false,
                'response' => '',
                'error' => 'Circuit breaker open: skipping SOAP call',
            ];
        }

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

            $responseText = is_scalar($response) ? (string) $response : (string) json_encode($response);

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
                'ack_text' => $responseText,
                'idempotency_key' => $this->generateIdempotencyKey($portId, $messageType, $xmlMessage),
            ]);

            $this->circuitBreaker->recordSuccess();

            return [
                'success' => true,
                'response' => $responseText,
                'error' => null,
            ];
        } catch (SoapFault $e) {
            $this->circuitBreaker->recordFailure();
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
                'response' => '',
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
        $result = [
            'success' => false,
            'response' => '',
            'error' => 'No attempts were performed.',
        ];

        while ($attempt < $maxRetries) {
            $result = $this->processNPCMsg($xmlMessage, $messageType, $portId);

            if ($result['success']) {
                return $result;
            }

            $attempt++;

            if ($attempt < $maxRetries) {
                // Exponential backoff using configured base delay
                $delayMs = $this->retryDelayMs * (2 ** ($attempt - 1));
                usleep($delayMs * 1000);
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
