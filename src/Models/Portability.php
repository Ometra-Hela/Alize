<?php

/**
 * Portability Model.
 *
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Models
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Portability Model.
 *
 * Represents a portability process in the database.
 *
 * @package Ometra\HelaAlize\Models
 */
class Portability extends Model
{
    /**
     * @var bool
     */
    public $timestamps = true;

    /**
     * @var array<string>
     */
    protected $fillable = [
        'port_id',
        'folio_id',
        'state',
        'port_type',
        'subscriber_type',
        'dida',
        'dcr',
        'rida',
        'rcr',
        'req_port_exec_date',
        'port_exec_date',
        't1_expires_at',
        't3_expires_at',
        't4_expires_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'req_port_exec_date' => 'datetime',
        'port_exec_date' => 'datetime',
        't1_expires_at' => 'datetime',
        't3_expires_at' => 'datetime',
        't4_expires_at' => 'datetime',
    ];

    /**
     * Constructor.
     *
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('alize.table_prefix', 'alize_') . 'portabilities';
    }

    /**
     * Get the numbers associated with the portability.
     *
     * @return HasMany
     */
    public function numbers(): HasMany
    {
        return $this->hasMany(PortabilityNumber::class, 'portability_id');
    }
}
