<?php

namespace Ometra\HelaAlize\Tests\Unit\Console;

use Mockery;
use Ometra\HelaAlize\Services\DailyFileReconciliator;
use Ometra\HelaAlize\Services\SftpClient;
use Ometra\HelaAlize\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ReconcileDailyFilesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_reconciles_daily_files()
    {
        // Mocks
        $sftp = Mockery::mock(SftpClient::class);
        $reconciliator = Mockery::mock(DailyFileReconciliator::class);

        $date = '20250101';
        $files = ['/tmp/file1.txt'];
        $stats = ['processed' => 10, 'updated' => 2, 'errors' => 0];

        // Expectations
        $sftp->shouldReceive('downloadDailyFiles')
            ->once()
            ->with($date)
            ->andReturn($files);

        $reconciliator->shouldReceive('reconcile')
            ->once()
            ->with($files[0])
            ->andReturn($stats);

        $this->app->instance(SftpClient::class, $sftp);
        $this->app->instance(DailyFileReconciliator::class, $reconciliator);

        $this->artisan('numlex:reconcile', ['date' => $date])
            ->expectsOutputToContain("Starting reconciliation for date: $date")
            ->expectsOutputToContain('Processing file: file1.txt')
            ->expectsOutputToContain('Reconciliation complete.')
            ->assertExitCode(0);
    }
}
