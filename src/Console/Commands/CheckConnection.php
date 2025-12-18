<?php

namespace Ometra\HelaAlize\Console\Commands;

use Illuminate\Console\Command;
use Ometra\HelaAlize\Soap\NumlexSoapClient;

/**
 * Check Connection Command.
 *
 * Diagnostic tool to verify TCP connectivity, SSL certificates,
 * and SOAP client initialization for the NUMLEX service.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Console\Commands
 * @author  HELA Development Team
 * @license MIT
 */
class CheckConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'numlex:check-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifies connectivity issues with NUMLEX SOAP Endpoint';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info("Checking connectivity to NUMLEX...");

        $endpoint = config('alize.soap.client_endpoint');

        if (!is_string($endpoint) || $endpoint === '') {
            $this->error('Invalid or missing NUMLEX endpoint configuration (alize.soap.client_endpoint).');

            return self::FAILURE;
        }

        $this->comment("Endpoint: $endpoint");

        // 1. TCP Check
        $host = parse_url($endpoint, PHP_URL_HOST);
        $port = parse_url($endpoint, PHP_URL_PORT);

        if (!is_string($host) || $host === '') {
            $this->error('Unable to parse host from endpoint URL.');

            return self::FAILURE;
        }

        $port = is_int($port) ? $port : 443;

        $this->info("1. Testing TCP Connection to $host:$port...");
        if ($this->checkTcp($host, $port)) {
            $this->info("   [OK] TCP Connection successful.");
        } else {
            $this->error("   [FAIL] TCP Connection refused or timed out.");

            return self::FAILURE;
        }

        // 2. SSL/TLS Cert Check
        $certPath = config('alize.soap.tls.cert_path');
        $keyPath = config('alize.soap.tls.key_path');
        $caPath = config('alize.soap.tls.ca_path');

        $this->info("2. Verifying Local Certificates...");
        $hasTlsConfig = is_string($certPath) && is_string($keyPath) && is_string($caPath)
            && ($certPath !== '' || $keyPath !== '' || $caPath !== '');

        if (! $hasTlsConfig) {
            $this->comment("   [SKIP] TLS certificates not configured; skipping local file check.");
        } elseif (file_exists((string) $certPath) && file_exists((string) $keyPath) && file_exists((string) $caPath)) {
            $this->info("   [OK] Certificates found.");
        } else {
            $this->error("   [FAIL] Certificate files missing.");
            $this->error("   Cert: $certPath");
            $this->error("   Key:  $keyPath");
            $this->error("   CA:   $caPath");

            return self::FAILURE;
        }

        // 3. SOAP Instantiation Check
        $this->info("3. Initializing SOAP Client...");

        try {
            new NumlexSoapClient();
            $this->info("   [OK] SOAP Client initialized (WSDL/XSD loaded if applicable).");
        } catch (\Exception $e) {
            $this->error("   [FAIL] SOAP Init failed: " . $e->getMessage());

            return self::FAILURE;
        }

        $this->info('Connectivity check passed.');

        return self::SUCCESS;
    }

    private function checkTcp(string $host, int $port): bool
    {
        $connection = @fsockopen($host, $port, $errno, $errstr, 5);
        if (is_resource($connection)) {
            fclose($connection);

            return true;
        }

        return false;
    }
}
