<?php

/**
 * NUMLEX message type enumeration.
 *
 * Defines all message types supported by the NUMLEX/ABD v2.1 specification.
 * Each message type represents a specific action in the portability workflow.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Enums
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Enums;

enum MessageType: int
{
    // Portation messages (1001-1093)
    case PORT_REQUEST = 1001;
    case PORT_REQUEST_ACK = 1002;
    case PORT_REQUEST_TO_DIDA = 1003;
    case PORT_RESPONSE = 1004;
    case READY_TO_SCHEDULE = 1005;
    case SCHEDULE_PORT_REQUEST = 1006;
    case SCHEDULE_PORT_NOTIFICATION = 1007;
    case PORT_REJECTED = 1091;
    case PORT_TERMINATED = 1092;
    case PARTIAL_REJECTION = 1093;

    // Individual validation (PF) messages (1201-1203)
    case INDIVIDUAL_VALIDATION_REQUEST = 1201;
    case INDIVIDUAL_VALIDATION_RESPONSE = 1202;
    case INDIVIDUAL_VALIDATION_TO_RIDA = 1203;

    // NIP generation messages (2001-2005)
    case PIN_GENERATION_REQUEST = 2001;
    case PIN_DELIVERY_CONFIRMATION = 2002;
    case PIN_CONFIRMATION = 2004;
    case PIN_NOTIFICATION = 2005;

    // Cancellation messages (3001-3002)
    case CANCELLATION_REQUEST = 3001;
    case CANCELLATION_ACCEPTANCE = 3002;

    // Reversal messages (4001-4005)
    case REVERSAL_REQUEST = 4001;
    case REVERSAL_DOCS_REQUEST = 4002;
    case REVERSAL_DOCS_RESPONSE = 4003;
    case REVERSAL_ACCEPTANCE = 4004;
    case REVERSAL_REJECTION = 4005;

    // Deletion messages (5001-5002)
    case DELETION_REQUEST = 5001;
    case DELETION_RESPONSE = 5002;

    // Non-geographic number messages (6001-6002)
    case NON_GEO_ALTA_REQUEST = 6001;
    case NON_GEO_ALTA_RESPONSE = 6002;

    // Synchronization messages (7001-7002)
    case SYNC_REQUEST = 7001;
    case SYNC_RESPONSE = 7002;

    // IDA/CR association messages (8101-8202)
    case IDA_ASSOCIATION_REQUEST = 8101;
    case IDA_ASSOCIATION_RESPONSE = 8102;
    case CR_ASSOCIATION_REQUEST = 8201;
    case CR_ASSOCIATION_RESPONSE = 8202;

    /**
     * Returns human-readable name for the message type.
     *
     * @return string Message type description
     */
    public function label(): string
    {
        return match ($this) {
            self::PORT_REQUEST => 'Port Request',
            self::PORT_REQUEST_ACK => 'Port Request Acknowledgment',
            self::PORT_REQUEST_TO_DIDA => 'Port Request to DIDA',
            self::PORT_RESPONSE => 'Port Response',
            self::READY_TO_SCHEDULE => 'Ready to Schedule',
            self::SCHEDULE_PORT_REQUEST => 'Schedule Port Request',
            self::SCHEDULE_PORT_NOTIFICATION => 'Schedule Port Notification',
            self::PORT_REJECTED => 'Port Rejected',
            self::PORT_TERMINATED => 'Port Terminated',
            self::PARTIAL_REJECTION => 'Partial Rejection',
            self::INDIVIDUAL_VALIDATION_REQUEST => 'Individual Validation Request',
            self::INDIVIDUAL_VALIDATION_RESPONSE => 'Individual Validation Response',
            self::INDIVIDUAL_VALIDATION_TO_RIDA => 'Individual Validation to RIDA',
            self::PIN_GENERATION_REQUEST => 'PIN Generation Request',
            self::PIN_DELIVERY_CONFIRMATION => 'PIN Delivery Confirmation',
            self::PIN_CONFIRMATION => 'PIN Confirmation',
            self::PIN_NOTIFICATION => 'PIN Notification',
            self::CANCELLATION_REQUEST => 'Cancellation Request',
            self::CANCELLATION_ACCEPTANCE => 'Cancellation Acceptance',
            self::REVERSAL_REQUEST => 'Reversal Request',
            self::REVERSAL_DOCS_REQUEST => 'Reversal Documents Request',
            self::REVERSAL_DOCS_RESPONSE => 'Reversal Documents Response',
            self::REVERSAL_ACCEPTANCE => 'Reversal Acceptance',
            self::REVERSAL_REJECTION => 'Reversal Rejection',
            self::DELETION_REQUEST => 'Deletion Request',
            self::DELETION_RESPONSE => 'Deletion Response',
            self::NON_GEO_ALTA_REQUEST => 'Non-Geographic Alta Request',
            self::NON_GEO_ALTA_RESPONSE => 'Non-Geographic Alta Response',
            self::SYNC_REQUEST => 'Synchronization Request',
            self::SYNC_RESPONSE => 'Synchronization Response',
            self::IDA_ASSOCIATION_REQUEST => 'IDA Association Request',
            self::IDA_ASSOCIATION_RESPONSE => 'IDA Association Response',
            self::CR_ASSOCIATION_REQUEST => 'CR Association Request',
            self::CR_ASSOCIATION_RESPONSE => 'CR Association Response',
        };
    }

    /**
     * Checks if message type is part of portation flow.
     *
     * @return bool True if portation message
     */
    public function isPortationMessage(): bool
    {
        return $this->value >= 1001 && $this->value <= 1093;
    }

    /**
     * Checks if message type requires attachments support.
     *
     * @return bool True if attachments may be included
     */
    public function supportsAttachments(): bool
    {
        return match ($this) {
            self::PORT_REQUEST,
            self::PORT_REQUEST_TO_DIDA,
            self::REVERSAL_DOCS_REQUEST,
            self::REVERSAL_DOCS_RESPONSE => true,
            default => false,
        };
    }
}
