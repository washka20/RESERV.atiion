<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Eloquent-модель для outbox_messages. Infrastructure-only (не domain entity).
 *
 * Поля status: pending | published | failed. retry_count увеличивается при фейле.
 * next_attempt_at — когда worker'у можно взять сообщение следующий раз (exponential backoff).
 *
 * @property string $id
 * @property string $aggregate_id
 * @property string $event_type
 * @property array<string, mixed> $payload
 * @property string $status
 * @property int $retry_count
 * @property Carbon|null $next_attempt_at
 * @property Carbon|null $published_at
 * @property Carbon|null $failed_at
 * @property string|null $last_error
 */
final class OutboxMessageModel extends Model
{
    protected $table = 'outbox_messages';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'retry_count' => 'int',
        'next_attempt_at' => 'datetime',
        'published_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
