<?php

namespace Ometra\HelaAlize\Tests\Unit;

use Ometra\HelaAlize\Exceptions\InvalidConfigurationException;
use Ometra\HelaAlize\HelaAlizeServiceProvider;
use Ometra\HelaAlize\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ConfigValidationTest extends TestCase
{
    #[Test]
    public function itFailsFastOnMissingCredentials()
    {
        // Missing creds
        config(['alize.soap.user_id' => '']);
        config(['alize.soap.password_b64' => '']);
        config(['alize.soap.client_endpoint' => 'https://example.com']);
        config(['alize.soap.tls.cert_path' => __FILE__]);
        config(['alize.soap.tls.key_path' => __FILE__]);
        config(['alize.soap.tls.ca_path' => __FILE__]);
        config(['alize.soap.circuit_breaker.failure_threshold' => 1]);
        config(['alize.soap.circuit_breaker.open_seconds' => 1]);
        config(['alize.soap.circuit_breaker.half_open_successes' => 1]);

        $provider = new HelaAlizeServiceProvider($this->app);

        $this->expectException(InvalidConfigurationException::class);
        // Directly invoke boot which performs configuration validation
        $provider->boot();
    }
}
