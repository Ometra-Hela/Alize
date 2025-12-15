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
    public function handle()
    {
        $this->info("Checking connectivity to NUMLEX...");

        $endpoint = config('alize.soap.client_endpoint');
        $this->comment("Endpoint: $endpoint");

        // 1. TCP Check
        $host = parse_url($endpoint, PHP_URL_HOST);
        $port = parse_url($endpoint, PHP_URL_PORT) ?? 443;

        $this->info("1. Testing TCP Connection to $host:$port...");
        if ($this->checkTcp($host, $port)) {
            $this->info("   [OK] TCP Connection successful.");
        } else {
            $this->error("   [FAIL] TCP Connection refused or timed out.");

            return 1;
        }

        // 2. SSL/TLS Cert Check
        $certPath = config('alize.soap.tls.cert_path');
        $keyPath = config('alize.soap.tls.key_path');

        $this->info("2. Verifying Local Certificates...");
        if (file_exists($certPath) && file_exists($keyPath)) {
            $this->info("   [OK] Certificates found.");
        } else {
            $this->error("   [FAIL] Certificate files missing.");
            $this->error("   Cert: $certPath");
            $this->error("   Key:  $keyPath");

            return 1;
        }

        // 3. SOAP Instantiation Check
        $this->info("3. Initializing SOAP Client...");

        try {
            new NumlexSoapClient();
            $this->info("   [OK] SOAP Client initialized (WSDL/XSD loaded if applicable).");
        } catch (\Exception $e) {
            $this->error("   [FAIL] SOAP Init failed: " . $e->getMessage());

            return 1;
        }

        $this->info("Connectivity Check Passed ✅");

        return 0;
    }

    private function checkTcp($host, $port)
    {
        $connection = @fsockopen($host, $port, $errno, $errstr, 5);
        if (is_resource($connection)) {
            fclose($connection);

            return true;
        }

        return false;
    }
}
