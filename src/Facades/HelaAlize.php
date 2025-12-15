<?php

namespace Ometra\HelaAlize\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Ometra\HelaAlize\Models\Portability initiate(array $data)
 * @method static void schedule(\Ometra\HelaAlize\Models\Portability $portability)
 * @method static void cancel(\Ometra\HelaAlize\Models\Portability $portability, string $reason)
 * @method static \Ometra\HelaAlize\Enums\PortabilityState|null getState(string $portId)
 *
 * @see \Ometra\HelaAlize\Services\HelaAlizeService
 */
class HelaAlize extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Ometra\HelaAlize\Services\HelaAlizeService::class;
    }
}
