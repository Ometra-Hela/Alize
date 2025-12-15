<?php

/**
 * State Orchestrator.
 *
 * Manages portability state transitions according to ABD rules.
 * Updates timers, validates transitions, and triggers state-dependent actions.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Orchestration
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Orchestration;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Ometra\HelaAlize\Enums\PortabilityState;
use Ometra\HelaAlize\Exceptions\InvalidTransitionException;
use Ometra\HelaAlize\Models\Portability;
use Ometra\HelaAlize\Support\StateTransition;

class StateOrchestrator
{
    private StateTransition $stateTransition;

    public function __construct()
    {
        $this->stateTransition = new StateTransition();
    }

    /**
     * Transitions portability to new state.
     *
     * @param  Portability      $portability Portability instance
     * @param  PortabilityState $newState New state
     * @param  string|null      $reason Transition reason
     * @return void
     * @throws InvalidTransitionException When transition is not allowed
     */
    public function transition(
        Portability $portability,
        PortabilityState $newState,
        ?string $reason = null,
    ): void {
        if (!is_string($portability->state) || $portability->state === '') {
            throw new InvalidTransitionException('Portability state is missing.');
        }

        $currentState = PortabilityState::from($portability->state);

        // Validate transition
        $this->stateTransition->validateOrFail($currentState, $newState);

        $previousState = $currentState;
        $portability->state = $newState->value;
        $portability->save();

        // Dispatch state change event
        \Ometra\HelaAlize\Events\PortabilityStateChanged::dispatch(
            $portability,
            $previousState,
            $newState,
            $reason
        );

        // Update timers based on new state
        $this->updateTimers($portability, $newState);

        // Log transition
        Log::info('State transition', [
            'port_id' => $portability->port_id,
            'from' => $previousState->value,
            'to' => $newState->value,
            'reason' => $reason,
        ]);
    }

    /**
     * Updates timers based on state.
     *
     * @param  Portability      $portability Portability instance
     * @param  PortabilityState $state New state
     * @return void
     */
    private function updateTimers(
        Portability $portability,
        PortabilityState $state,
    ): void {
        $now = CarbonImmutable::now(config('alize.timezone'));

        match ($state) {
            PortabilityState::PORT_REQUESTED => $portability->t1_expires_at = $now->addMinutes(
                config('alize.timers.t1_timeout_minutes', 20)
            )->toMutable(),
            PortabilityState::READY_TO_BE_SCHEDULED => $portability->t3_expires_at = $now->addHours(
                config('alize.timers.t3_timeout_hours', 24)
            )->toMutable(),
            PortabilityState::PORT_SCHEDULED => $portability->t4_expires_at = $portability->port_exec_date,
            default => null,
        };
    }

    /**
     * Checks if timer has expired.
     *
     * @param  Portability $portability Portability instance
     * @param  string      $timer Timer name (t1, t3, t4, t5)
     * @return bool        True if expired
     */
    public function isTimerExpired(
        Portability $portability,
        string $timer,
    ): bool {
        $field = "{$timer}_expires_at";

        if (!isset($portability->$field)) {
            return false;
        }

        return CarbonImmutable::parse($portability->$field)
            ->isPast();
    }

    /**
     * Handles timer expiration.
     *
     * @param  Portability $portability Portability instance
     * @param  string      $timer Timer that expired
     * @return void
     */
    public function handleTimerExpiration(
        Portability $portability,
        string $timer,
    ): void {
        Log::warning('Timer expired', [
            'port_id' => $portability->port_id,
            'timer' => $timer,
            'state' => $portability->state,
        ]);

        // Auto-transition based on timer
        match ($timer) {
            't1' => $this->transition(
                $portability,
                PortabilityState::TERMINATED,
                'T1 timer expired',
            ),
            't3' => $this->transition(
                $portability,
                PortabilityState::TERMINATED,
                'T3 timer expired',
            ),
            't4' => $this->transition(
                $portability,
                PortabilityState::CANCELLED,
                'T4 timer expired',
            ),
            default => null,
        };
    }
}
