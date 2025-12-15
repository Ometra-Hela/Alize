<?php

/**
 * Portability Number Model.
 *
 * Stores individual MSISDNs associated with a portability request.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Models
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Portability Number Model.
 *
 * @property int $id
 * @property int $portability_id
 * @property string $msisdn_ported
 *
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, mixed $value)
 * @method static static|null first()
 * @method static static create(array $attributes)
 */
class PortabilityNumber extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'portability_id',
        'msisdn_ported',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('alize.table_prefix', 'alize_') . 'portability_numbers';
    }

    public function portability(): BelongsTo
    {
        return $this->belongsTo(Portability::class, 'portability_id');
    }
}
