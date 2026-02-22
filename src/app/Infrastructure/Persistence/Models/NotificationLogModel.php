<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int         $id
 * @property int         $notification_id
 * @property string      $channel
 * @property string      $status
 * @property string|null $error_message
 * @property \Carbon\Carbon $created_at
 */
final class NotificationLogModel extends Model
{
    protected $table = 'notification_logs';

    public $timestamps = false;

    protected $fillable = [
        'notification_id',
        'channel',
        'status',
        'error_message',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(NotificationModel::class, 'notification_id');
    }
}
