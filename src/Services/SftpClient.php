<?php

namespace Ometra\HelaAlize\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SftpClient
{
    protected $disk;

    public function __construct()
    {
        // We will define a dynamic disk config for SFTP
        // Host app must have league/flysystem-sftp-v3 installed
        $config = config('alize.sftp');

        config(['filesystems.disks.alize_sftp' => [
            'driver' => 'sftp',
            'host' => $config['host'],
            'username' => $config['user'],
            'privateKey' => $config['key_path'],
            'port' => $config['port'],
            'timeout' => 30,
        ]]);

        $this->disk = Storage::disk('alize_sftp');
    }

    /**
     * Download daily files for a given date.
     *
     * @param string $date YYYYMMDD
     * @return array List of downloaded file paths
     */
    public function downloadDailyFiles(string $date): array
    {
        $remotePath = str_replace(
            '<IDO>',
            config('alize.ida'),
            config('alize.sftp.daily_path')
        );

        // Files are usually named with date, e.g., PRT_yyyymmdd.txt
        // Or simply listing the directory and filtering

        try {
            $files = $this->disk->files($remotePath);
            $downloaded = [];

            $localDir = storage_path('app/alize/daily/' . $date);
            if (!file_exists($localDir)) {
                mkdir($localDir, 0755, true);
            }

            foreach ($files as $file) {
                if (str_contains($file, $date)) {
                    $content = $this->disk->get($file);
                    $filename = basename($file);
                    $localPath = $localDir . '/' . $filename;
                    file_put_contents($localPath, $content);
                    $downloaded[] = $localPath;

                    Log::info("Downloaded daily file: $filename");
                }
            }

            return $downloaded;
        } catch (\Exception $e) {
            Log::error("SFTP Download failed: " . $e->getMessage());

            throw $e;
        }
    }
}
