<?php

/**
 * Circuit Breaker.
 *
 * Provides a cache-backed circuit breaker for external integrations.
 * Opens the circuit after consecutive failures and prevents calls for a
 * configurable cool-down period. Supports half-open probing on recovery.
 *
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Support
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Support;

use Illuminate\Support\Facades\Cache;

class CircuitBreaker
{
    public function __construct(
        private string $name,
        private int $failureThreshold,
        private int $openSeconds,
        private int $halfOpenMaxSuccesses = 1,
    ) {
        //
    }

    /**
     * Whether a request is allowed given the current circuit state.
     */
    public function allowRequest(): bool
    {
        $state = $this->getState();

        if ($state['open'] === true) {
            $openedAt = $state['opened_at'] ?? 0;
            if (time() - $openedAt >= $this->openSeconds) {
                // Move to half-open
                $this->setState(open: false, failures: 0, halfOpen: true, halfOpenSuccesses: 0);

                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * Record a successful call.
     */
    public function recordSuccess(): void
    {
        $state = $this->getState();

        if (($state['half_open'] ?? false) === true) {
            $successes = (int) ($state['half_open_successes'] ?? 0) + 1;
            if ($successes >= $this->halfOpenMaxSuccesses) {
                // Close circuit
                $this->setState(open: false, failures: 0, halfOpen: false, halfOpenSuccesses: 0);
            } else {
                $this->setState(
                    open: false,
                    failures: 0,
                    halfOpen: true,
                    halfOpenSuccesses: $successes,
                );
            }
        } else {
            // Reset failures on success
            $this->setState(open: false, failures: 0, halfOpen: false, halfOpenSuccesses: 0);
        }
    }

    /**
     * Record a failed call.
     */
    public function recordFailure(): void
    {
        $state = $this->getState();
        $failures = (int) ($state['failures'] ?? 0) + 1;

        if ($failures >= $this->failureThreshold) {
            $this->setState(
                open: true,
                failures: $failures,
                openedAt: time(),
                halfOpen: false,
                halfOpenSuccesses: 0,
            );
        } else {
            $this->setState(
                open: false,
                failures: $failures,
                halfOpen: false,
                halfOpenSuccesses: 0,
            );
        }
    }

    /**
     * Get current state.
     *
     * @return array{open: bool, failures: int, opened_at?: int, half_open?: bool, half_open_successes?: int}
     */
    public function getState(): array
    {
        /** @var array{open: bool, failures: int, opened_at?: int, half_open?: bool, half_open_successes?: int}|null $state */
        $state = Cache::get($this->key(), null);

        if ($state === null) {
            return [
                'open' => false,
                'failures' => 0,
                'half_open' => false,
                'half_open_successes' => 0,
            ];
        }

        return $state;
    }

    private function setState(
        bool $open,
        int $failures,
        ?int $openedAt = null,
        bool $halfOpen = false,
        int $halfOpenSuccesses = 0,
    ): void {
        $state = [
            'open' => $open,
            'failures' => $failures,
            'half_open' => $halfOpen,
            'half_open_successes' => $halfOpenSuccesses,
        ];

        if ($openedAt !== null) {
            $state['opened_at'] = $openedAt;
        }

        // Use an eternal cache item; state transitions manage expiry via logic
        Cache::forever($this->key(), $state);
    }

    private function key(): string
    {
        return 'alize_cb_' . $this->name;
    }
}
