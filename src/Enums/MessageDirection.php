<?php

/**
 * Message direction enumeration.
 *
 * Indicates whether a message is being sent or received.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Enums
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Enums;

enum MessageDirection: string
{
    case INBOUND = 'IN';
    case OUTBOUND = 'OUT';

    /**
     * Returns human-readable label.
     *
     * @return string Direction label
     */
    public function label(): string
    {
        return match ($this) {
            self::INBOUND => 'Inbound',
            self::OUTBOUND => 'Outbound',
        };
    }
}
