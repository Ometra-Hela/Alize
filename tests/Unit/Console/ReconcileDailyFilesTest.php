<?php

namespace Ometra\HelaAlize\Tests\Unit\Console;

use Mockery;
use Ometra\HelaAlize\Console\Commands\ReconcileDailyFiles;
use Ometra\HelaAlize\Services\DailyFileReconciliator;
use Ometra\HelaAlize\Services\SftpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ReconcileDailyFilesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
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

        // Command Setup
        $command = new ReconcileDailyFiles();

        // Inject mocks via handle method requires Laravel's Service Container resolution
        // But since we are unit testing the logic, passing them is harder without the container.
        // We will simulate the handle method logic or use Laravel's test helpers if this was a Feature test.
        // Since this is a package unit test, we can manually invoke handle.

        // However, standard Illuminate Commands resolve dependencies from container.
        // Let's settle for a basic verified logic test by calling handle directly if possible, or refactoring command to accept deps in constructor (best practice).

        // Refactoring command to use constructor injection!
        $command = new ReconcileDailyFiles($sftp, $reconciliator);

        // We need to set up the input/output for the command
        $app = new Application();
        $app->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['date' => $date]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString("Starting reconciliation for date: $date", $output);
        $this->assertStringContainsString("Processing file: file1.txt", $output);
        $this->assertStringContainsString("Reconciliation complete.", $output);
    }
}
