<?php

/**
 * NPC Message model.
 *
 * Represents NUMLEX messages sent and received through SOAP interface.
 * Stores raw XML, parsed data, and tracking information for idempotency.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Models
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Ometra\HelaAlize\Enums\MessageDirection;
use Ometra\HelaAlize\Enums\MessageType;

/**
 * NPC Message Model.
 *
 * @property int $id
 * @property string|null $port_id
 * @property string|null $message_id
 * @property MessageDirection $direction
 * @property MessageType $type_code
 * @property string|null $sender
 * @property string|null $raw_xml
 * @property array|null $parsed_data
 * @property \Carbon\Carbon|null $sent_at
 * @property \Carbon\Carbon|null $received_at
 * @property string|null $ack_status
 * @property string|null $ack_text
 * @property int $retry_count
 * @property string|null $idempotency_key
 * @property \Carbon\Carbon|null $last_retry_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, mixed $value)
 * @method static static|null first()
 * @method static static create(array $attributes)
 * @method bool save(array $options = [])
 * @method bool update(array $attributes = [], array $options = [])
 * @method int increment(string $column, float|int $amount = 1, array $extra = [])
 */
class NpcMessage extends Model
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('alize.table_prefix', 'alize_') . 'npc_messages';
    }

    protected $fillable = [
        'port_id',
        'message_id',
        'direction',
        'type_code',
        'sender',
        'raw_xml',
        'parsed_data',
        'sent_at',
        'received_at',
        'ack_status',
        'ack_text',
        'retry_count',
        'idempotency_key',
        'last_retry_at',
    ];

    protected $casts = [
        'type_code' => MessageType::class,
        'direction' => MessageDirection::class,
        'parsed_data' => 'array',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'last_retry_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    /**
     * Relationship to portability process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function portability()
    {
        return $this->belongsTo(
            Portability::class,
            'port_id',
            'port_id',
        );
    }

    /**
     * Scope for in bound messages.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query Query builder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInbound($query)
    {
        return $query->where('direction', MessageDirection::INBOUND);
    }

    /**
     * Scope for outbound messages.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query Query builder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOutbound($query)
    {
        return $query->where('direction', MessageDirection::OUTBOUND);
    }

    /**
     * Scope for messages needing retry.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query Query builder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePendingRetry($query)
    {
        return $query->where('direction', MessageDirection::OUTBOUND)
            ->where('ack_status', '!=', 'SUCCESS')
            ->where(
                function ($q) {
                    $q->whereNull('last_retry_at')
                        ->orWhere('last_retry_at', '<', now()->subMinutes(5));
                },
            )
            ->where('retry_count', '<', 3);
    }

    /**
     * Checks if message is acknowledged successfully.
     *
     * @return bool True if successful
     */
    public function isAcknowledged(): bool
    {
        return $this->ack_status === 'SUCCESS';
    }

    /**
     * Marks message as sent.
     *
     * @param string $ackStatus Acknowledgment status
     * @param string $ackText   Acknowledgment text
     * @return void
     */
    public function markAsSent(
        string $ackStatus,
        string $ackText,
    ): void {
        $this->update([
            'sent_at' => CarbonImmutable::now(),
            'ack_status' => $ackStatus,
            'ack_text' => $ackText,
        ]);
    }

    /**
     * Increments retry counter.
     *
     * @return void
     */
    public function incrementRetry(): void
    {
        $this->increment('retry_count');
        $this->update(['last_retry_at' => CarbonImmutable::now()]);
    }
}
