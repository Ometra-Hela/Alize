<?php

/**
 * Portability state enumeration.
 *
 * Defines all possible states in the ABD portability state machine.
 * States must match ABD specification exactly for compliance.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Enums
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Enums;

enum PortabilityState: string
{
    case INITIAL = 'INITIAL';
    case PIN_REQUESTED = 'PIN_REQUESTED';
    case PIN_DELIVERY_CONF = 'PIN_DELIVERY_CONF';
    case PORT_REQUESTED = 'PORT_REQUESTED';
    case PORT_INDVAL_REQUESTED = 'PORT_INDVAL_REQUESTED';
    case READY_TO_BE_SCHEDULED = 'READY_TO_BE_SCHEDULED';
    case REJECT_PENDING = 'REJECT_PENDING';
    case PORT_SCHEDULED = 'PORT_SCHEDULED';
    case PORTED = 'PORTED';
    case CANCELLED = 'CANCELLED';
    case REVERSAL_REQUESTED = 'REVERSAL_REQUESTED';
    case REVERSAL_DOCS_REQUESTED = 'REVERSAL_DOCS_REQUESTED';
    case REVERSAL_SCHEDULED = 'REVERSAL_SCHEDULED';
    case REVERSED = 'REVERSED';
    case DELETED = 'DELETED';
    case TERMINATED = 'TERMINATED';
    case REJECTED = 'REJECTED';

    /**
     * Returns human-readable label.
     *
     * @return string State label
     */
    public function label(): string
    {
        return match ($this) {
            self::INITIAL => 'Initial',
            self::PIN_REQUESTED => 'PIN Requested',
            self::PIN_DELIVERY_CONF => 'PIN Delivery Confirmed',
            self::PORT_REQUESTED => 'Port Requested',
            self::PORT_INDVAL_REQUESTED => 'Individual Validation Requested',
            self::READY_TO_BE_SCHEDULED => 'Ready to be Scheduled',
            self::REJECT_PENDING => 'Rejection Pending',
            self::PORT_SCHEDULED => 'Port Scheduled',
            self::PORTED => 'Ported',
            self::CANCELLED => 'Cancelled',
            self::REVERSAL_REQUESTED => 'Reversal Requested',
            self::REVERSAL_DOCS_REQUESTED => 'Reversal Documents Requested',
            self::REVERSAL_SCHEDULED => 'Reversal Scheduled',
            self::REVERSED => 'Reversed',
            self::DELETED => 'Deleted',
            self::TERMINATED => 'Terminated',
            self::REJECTED => 'Rejected',
        };
    }

    /**
     * Checks if state is terminal (no further transitions).
     *
     * @return bool True if terminal state
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::PORTED,
            self::CANCELLED,
            self::REVERSED,
            self::DELETED,
            self::TERMINATED,
            self::REJECTED => true,
            default => false,
        };
    }

    /**
     * Checks if cancellation is allowed from this state.
     *
     * @return bool True if cancellation permitted
     */
    public function canCancel(): bool
    {
        return match ($this) {
            self::PORT_REQUESTED,
            self::PORT_INDVAL_REQUESTED,
            self::READY_TO_BE_SCHEDULED,
            self::PORT_SCHEDULED => true,
            default => false,
        };
    }
}
