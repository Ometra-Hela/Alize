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

class PortabilityMsisdn extends Model
{
    use HasCompositePrimaryKey;

    protected $table          = 'PortabilitiesMsisdn';

    protected $primaryKey     = ['id_portability', 'msisdn_ported'];

    public $timestamps     = false;

    public $incrementing   = false;

    protected $guarded = [];

    public function portability()
    {
        return $this->belongsTo('Ometra\HelaAlize\Models\Portability', 'id_portability', 'id_portability');
    }

    public function service()
    {
        return $this->belongsTo('Ometra\HelaAlize\Models\Service', 'msisdn_transitory', 'msisdn');
    }
}
