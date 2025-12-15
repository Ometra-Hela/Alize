<?php

/**
 * Portability MSISDN Model.
 *
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Models
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Models;

use Equidna\Toolkit\Traits\Database\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortabilityMsisdn extends Model
{
    use HasCompositePrimaryKey;

    protected $table          = 'PortabilitiesMsisdn';

    public $timestamps     = false;

    public $incrementing   = false;

    protected $guarded = [];

    public function portability(): BelongsTo
    {
        return $this->belongsTo(Portability::class, 'id_portability', 'id_portability');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'msisdn_transitory', 'msisdn');
    }
}
