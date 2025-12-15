<?php

namespace Ometra\HelaAlize\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ometra\HelaAlize\Models\Portability;

/**
 * Dispatched when portability is scheduled and execution date is confirmed.
 *
 * Host application should listen to this event to send notifications to clients.
 * Contains portability details including execution date.
 */
class PortabilityScheduled
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Portability $portability,
    ) {
    }
}
