<?php

namespace Ometra\HelaAlize\Tests\Integration;

use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Soap\NumlexSoapClient;
use Ometra\HelaAlize\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use SoapClient;
use SoapFault;

class SoapClientCircuitBreakerTest extends TestCase
{
    private function withCircuitBreakerConfig(): void
    {
        config(['alize.soap.circuit_breaker.failure_threshold' => 2]);
        config(['alize.soap.circuit_breaker.open_seconds' => 60]);
        config(['alize.soap.circuit_breaker.half_open_successes' => 1]);
        config(['alize.soap.retry_delay_ms' => 10]);
        config(['alize.soap.user_id' => 'user']);
        config(['alize.soap.password_b64' => 'password']);
        config(['alize.soap.client_endpoint' => 'https://example.com']);
        config(['alize.soap.tls.cert_path' => __FILE__]);
        config(['alize.soap.tls.key_path' => __FILE__]);
        config(['alize.soap.tls.ca_path' => __FILE__]);
    }

    #[Test]
    public function itOpensCircuitAfterConsecutiveFailures()
    {
        $this->withCircuitBreakerConfig();

        $client = new class() extends NumlexSoapClient {
            protected function makeSoapClient(array $options): SoapClient
            {
                // Return a stub SoapClient instance; we'll force __soapCall to throw
                return new class(null, $options) extends SoapClient {
                    //
                };
            }

            public function processNPCMsg(string $xmlMessage, MessageType $messageType, string $portId): array
            {
                // Simulate a SOAP fault each call
                throw new SoapFault('Client', 'Simulated fault');
            }
        };

        $result1 = $client->sendWithRetry('<xml/>', MessageType::PORT_REQUEST, 'PORT1', 1);
        $this->assertFalse($result1['success']);

        $result2 = $client->sendWithRetry('<xml/>', MessageType::PORT_REQUEST, 'PORT1', 1);
        $this->assertFalse($result2['success']);

        // Third attempt should be blocked by circuit breaker
        $result3 = $client->sendWithRetry('<xml/>', MessageType::PORT_REQUEST, 'PORT1', 1);
        $this->assertFalse($result3['success']);
        $this->assertStringContainsString('Circuit breaker open', (string) $result3['error']);
    }
}
