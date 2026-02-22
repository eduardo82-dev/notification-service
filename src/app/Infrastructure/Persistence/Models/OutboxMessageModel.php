<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int         $id
 * @property string      $aggregate_type
 * @property string      $aggregate_id
 * @property string      $event_type
 * @property array       $payload
 * @property bool        $processed
 * @property int         $attempts
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $processed_at
 */
final class OutboxMessageModel extends Model
{
    protected $table = 'outbox_messages';

    public $timestamps = false;

    protected $fillable = [
        'aggregate_type',
        'aggregate_id',
        'event_type',
        'payload',
        'processed',
        'attempts',
        'created_at',
        'processed_at',
    ];

    protected $casts = [
        'payload'      => 'array',
        'processed'    => 'boolean',
        'created_at'   => 'datetime',
        'processed_at' => 'datetime',
    ];
}
