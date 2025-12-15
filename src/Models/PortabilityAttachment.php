<?php

namespace Ometra\HelaAlize\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Portability Attachment Model.
 *
 * Represents a file attached to a portability request.
 *
 * @package Ometra\HelaAlize\Models
 */
class PortabilityAttachment extends Model
{
    /**
     * @var array<string>
     */
    protected $fillable = [
        'portability_id',
        'file_name',
        'mime_type',
        'file_size',
        'path',
    ];

    /**
     * Constructor.
     *
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('alize.table_prefix', 'alize_') . 'portability_attachments';
    }

    /**
     * Get the portability that owns the attachment.
     *
     * @return BelongsTo
     */
    public function portability(): BelongsTo
    {
        return $this->belongsTo(Portability::class, 'portability_id');
    }
}
