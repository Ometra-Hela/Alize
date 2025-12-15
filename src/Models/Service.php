<?php

/**
 * Service Model.
 *
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Models
 * @author  HELA Development Team
 * @license MIT
 */

declare(strict_types=1);

namespace Ometra\HelaAlize\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a service record associated with an MSISDN.
 */
class Service extends Model
{
    public $timestamps = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('alize.table_prefix', 'alize_') . 'services';
    }
}
