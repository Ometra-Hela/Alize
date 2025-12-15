<?php

namespace Ometra\HelaAlize\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ometra\HelaAlize\Enums\PortabilityState;
use Ometra\HelaAlize\Models\Portability;

class PortabilityStateChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Portability $portability,
        public PortabilityState $previousState,
        public PortabilityState $newState,
        public ?string $reason = null,
    ) {
    }
}
