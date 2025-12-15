<?php

namespace Ometra\HelaAlize\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ometra\HelaAlize\Models\NpcMessage;

class InboundMessageReceived
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param NpcMessage $npcMessage
     */
    public function __construct(
        public NpcMessage $npcMessage
    ) {
    }
}
