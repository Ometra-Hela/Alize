<?php

/**
 * Daily File Reconciliator.
 *
 * Reconciles NUMLEX daily files with local database portability states.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Services
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Services;

use Carbon\Carbon;
use Equidna\Toolkit\Exceptions\BadRequestException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Ometra\HelaAlize\Enums\PortabilityState;
use Ometra\HelaAlize\Events\PortabilityStateChanged;
use Ometra\HelaAlize\Models\Portability;

class DailyFileReconciliator
{
    /**
     * Reconcile a daily file content with database.
     *
     * @param string $filePath
     * @return array stats
     */
    /**
     * Reconcile a daily file content with database.
     *
     * Mockup implementation: Expects PIPE delimited file.
     * Format: PortID|FolioID|Status|ExecDate
     *
     * @param string $filePath
     * @return array stats
     */
    public function reconcile(string $filePath): array
    {
        $stats = ['processed' => 0, 'updated' => 0, 'errors' => 0];

        if (!file_exists($filePath)) {
            Log::error("Daily file not found: $filePath");

            return $stats;
        }

        $handle = fopen($filePath, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                $stats['processed']++;

                // MOCKUP PARSER: PortId|Folio|Status|Date
                $parts = explode('|', $line);

                if (count($parts) < 3) {
                    Log::warning("Skipping invalid line in daily file: $line");
                    continue;
                }

                $portId = trim($parts[0]);
                // $folioId = trim($parts[1]); // Not used for lookup yet
                $statusRaw = trim($parts[2]);
                $execDateRaw = isset($parts[3]) ? trim($parts[3]) : null;

                try {
                    $portability = Portability::where('port_id', $portId)->first();

                    if ($portability) {
                        $newStatus = \Ometra\HelaAlize\Enums\PortabilityState::tryFrom($statusRaw);

                        if (!$newStatus) {
                            // Try mapping common aliases if needed, or skip
                            // For now assume file matches Enum values e.g. 'PORTED', 'CANCELLED'
                            Log::warning("Unknown status in daily file: $statusRaw");
                            continue;
                        }

                        // Update state if different
                        if ($portability->state !== $newStatus->value) {
                            $previousState = \Ometra\HelaAlize\Enums\PortabilityState::tryFrom((string) $portability->state)
                                ?? \Ometra\HelaAlize\Enums\PortabilityState::INITIAL;

                            $portability->state = $newStatus->value;

                            if ($execDateRaw) {
                                // Try parsing date
                                try {
                                    $portability->port_exec_date = \Carbon\Carbon::parse($execDateRaw);
                                } catch (\Exception $e) {
                                }
                            }

                            $portability->save();

                            // Dispatch event
                            \Ometra\HelaAlize\Events\PortabilityStateChanged::dispatch(
                                $portability,
                                $previousState,
                                $newStatus,
                                'Daily File Reconciliation'
                            );

                            $stats['updated']++;

                            Log::info("Reconciled Portability $portId to status {$newStatus->value}");
                        }
                    } else {
                        // Portability not found - maybe log or ignore?
                        // Log::debug("Portability $portId not found in local DB during reconciliation.");
                    }
                } catch (QueryException $e) {
                    $stats['errors']++;
                    Log::error("Database error during reconciliation for $portId", [
                        'error' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ]);
                } catch (\InvalidArgumentException $e) {
                    $stats['errors']++;
                    Log::warning("Invalid state transition during reconciliation for $portId", [
                        'error' => $e->getMessage(),
                    ]);
                } catch (\Throwable $e) {
                    $stats['errors']++;
                    Log::error("Unexpected error during reconciliation for $portId", [
                        'exception' => get_class($e),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            fclose($handle);
        }

        return $stats;
    }
}
