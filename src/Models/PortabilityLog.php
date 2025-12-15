<?php

/**
 * Portability Log Model.
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

class PortabilityLog extends Model
{
    use HasCompositePrimaryKey;

    protected $table          = 'PortabilitiesLog';

    protected $primaryKey     = 'id_portabilityLog';

    public $timestamps     = false;

    public $incrementing   = false;

    protected $guarded = [];

    public function portability()
    {
        return $this->belongsTo(Portability::class, 'id_portability', 'id_portability');
    }
}
