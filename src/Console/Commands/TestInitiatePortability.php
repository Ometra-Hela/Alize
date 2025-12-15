<?php

namespace Ometra\HelaAlize\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Ometra\HelaAlize\Facades\HelaAlize;

/**
 * Test Initiate Portability Command.
 *
 * Interactive tool to manually trigger a Portability Request (1001)
 * for testing purposes.
 *
 * @package Ometra\HelaAlize\Console\Commands
 */
class TestInitiatePortability extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'numlex:test-initiate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactively test portability initiation';

    /**
     * Execute the console command.
     *
     * @return int|void
     */
    public function handle()
    {
        $this->alert("NUMLEX Portability Test Tool");

        if (!$this->confirm('This will attempt to start a REAL portability (1001) flow. Continue?', false)) {
            return;
        }

        $dida = $this->ask('DIDA (Donating Carrier)', '001'); // 001 = Telcel dummy
        $dn = $this->ask('Number to Port (10 digits)');

        if (strlen($dn) !== 10) {
            $this->error("Invalid number length.");

            return 1;
        }

        $this->info("Initiating request for $dn from DIDA $dida...");

        try {
            $portability = HelaAlize::initiate([
                'dida' => $dida,
                'dcr' => '09', // CDMX
                'rcr' => '09',
                'port_type' => 'MOBILE',
                'subscriber_type' => 'INDIVIDUAL',
                'numbers' => [
                    ['start' => $dn, 'end' => $dn]
                ],
                // Default req time
                'subs_req_time' => now()->format('YmdHis'),
            ]);

            $this->table(
                ['Field', 'Value'],
                [
                    ['Port ID', $portability->port_id],
                    ['Folio ID', $portability->folio_id],
                    ['State', $portability->state],
                    ['Exec Date', $portability->req_port_exec_date->toDateString()],
                ]
            );

            $this->info("✅ Portability Initiated Successfully!");

        } catch (\Exception $e) {
            $this->error("❌ Initiation Failed: " . $e->getMessage());
            $this->line("Stack trace:");
            $this->line($e->getTraceAsString());
        }
    }
}
