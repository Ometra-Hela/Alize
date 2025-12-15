<?php

/**
 * Reconcile Daily Files Command.
 *
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Console\Commands
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Console\Commands;

use Illuminate\Console\Command;
use Ometra\HelaAlize\Services\DailyFileReconciliator;
use Ometra\HelaAlize\Services\SftpClient;

class ReconcileDailyFiles extends Command
{
    protected $signature = 'numlex:reconcile {date? : Date to process YYYYMMDD}';

    protected $description = 'Download and reconcile daily files from NUMLEX';

    public function __construct(
        protected SftpClient $sftp,
        protected DailyFileReconciliator $reconciliator
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dateArgument = $this->argument('date');
        $date = is_string($dateArgument) && $dateArgument !== ''
            ? $dateArgument
            : now()->subDay()->format('Ymd');

        $this->info("Starting reconciliation for date: $date");

        try {
            $files = $this->sftp->downloadDailyFiles($date);

            if (empty($files)) {
                $this->warn("No files found for date $date");

                return self::SUCCESS;
            }

            foreach ($files as $file) {
                $this->info("Processing file: " . basename($file));
                $stats = $this->reconciliator->reconcile($file);

                $this->table(
                    ['Metric', 'Count'],
                    [
                        ['Processed', $stats['processed']],
                        ['Updated', $stats['updated']],
                        ['Errors', $stats['errors']],
                    ]
                );
            }

            $this->info("Reconciliation complete.");
        } catch (\Exception $e) {
            $this->error("Reconciliation failed: " . $e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
