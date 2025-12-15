<?php

namespace Ometra\HelaAlize\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ometra\HelaAlize\Models\Portability;

/**
 * Dispatched when portability is ready to be scheduled (1005 received).
 *
 * Host application should listen to this event to notify admins/users
 * that they need to schedule the portability within T3 window (24 hours).
 */
class PortabilityReadyToSchedule
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Portability $portability,
    ) {
    }
}
