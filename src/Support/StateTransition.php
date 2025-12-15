<?php

/**
 * State Transition Validator.
 *
 * Validates portability state transitions according to ABD rules.
 * Ensures state machine integrity and compliance with NUMLEX specification.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Support
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Support;

use Ometra\HelaAlize\Enums\PortabilityState;
use Ometra\HelaAlize\Exceptions\InvalidTransitionException;

class StateTransition
{
    /**
     * Valid state transitions map.
     *
     * @var array<string, array<string>>
     */
    private const TRANSITIONS = [
        'INITIAL' => ['PIN_REQUESTED', 'PORT_REQUESTED'],
        'PIN_REQUESTED' => ['PIN_DELIVERY_CONF', 'REJECTED', 'TERMINATED'],
        'PIN_DELIVERY_CONF' => ['PORT_REQUESTED', 'TERMINATED'],
        'PORT_REQUESTED' => [
            'PORT_INDVAL_REQUESTED',
            'READY_TO_BE_SCHEDULED',
            'REJECT_PENDING',
            'CANCELLED',
            'REJECTED',
            'TERMINATED',
        ],
        'PORT_INDVAL_REQUESTED' => [
            'READY_TO_BE_SCHEDULED',
            'CANCELLED',
            'REJECTED',
            'TERMINATED',
        ],
        'READY_TO_BE_SCHEDULED' => [
            'PORT_SCHEDULED',
            'CANCELLED',
            'TERMINATED',
        ],
        'REJECT_PENDING' => ['REJECTED', 'TERMINATED'],
        'PORT_SCHEDULED' => [
            'PORTED',
            'CANCELLED',
            'REVERSAL_REQUESTED',
        ],
        'PORTED' => ['REVERSAL_REQUESTED', 'DELETED'],
        'REVERSAL_REQUESTED' => [
            'REVERSAL_DOCS_REQUESTED',
            'REVERSAL_SCHEDULED',
            'REJECTED',
        ],
        'REVERSAL_DOCS_REQUESTED' => ['REVERSAL_SCHEDULED', 'REJECTED'],
        'REVERSAL_SCHEDULED' => ['REVERSED'],
        'CANCELLED' => [],
        'REVERSED' => [],
        'DELETED' => [],
        'TERMINATED' => [],
        'REJECTED' => [],
    ];

    /**
     * Validates if transition is allowed.
     *
     * @param  PortabilityState $from Current state
     * @param  PortabilityState $to   Target state
     * @return bool             True if transition is valid
     */
    public function isAllowed(
        PortabilityState $from,
        PortabilityState $to,
    ): bool {
        $allowedTransitions = self::TRANSITIONS[$from->value] ?? [];

        return in_array($to->value, $allowedTransitions, true);
    }

    /**
     * Gets allowed next states from current state.
     *
     * @param  PortabilityState        $currentState Current state
     * @return array<PortabilityState> Allowed next states
     */
    public function getAllowedNextStates(PortabilityState $currentState): array
    {
        $allowedValues = self::TRANSITIONS[$currentState->value] ?? [];

        return array_map(
            fn($value) => PortabilityState::from($value),
            $allowedValues,
        );
    }

    /**
     * Validates transition or throws exception.
     *
     * @param  PortabilityState        $from Current state
     * @param  PortabilityState        $to   Target state
     * @return void
     * @throws InvalidTransitionException When transition is not allowed
     */
    public function validateOrFail(
        PortabilityState $from,
        PortabilityState $to,
    ): void {
        if (!$this->isAllowed($from, $to)) {
            throw new InvalidTransitionException(
                sprintf(
                    'Invalid state transition from %s to %s',
                    $from->value,
                    $to->value,
                ),
            );
        }
    }
}
