<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int         $id
 * @property string      $uuid
 * @property int         $user_id
 * @property int         $type_id
 * @property int         $status_id
 * @property array       $payload
 * @property string      $priority
 * @property string      $channels
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
final class NotificationModel extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'uuid',
        'user_id',
        'type_id',
        'status_id',
        'payload',
        'priority',
        'channels',
    ];

    protected $casts = [
        'payload'  => 'array',
        'channels' => 'array',
    ];

    public function type(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(NotificationTypeModel::class, 'type_id');
    }

    public function status(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(NotificationStatusModel::class, 'status_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLogModel::class, 'notification_id');
    }
}
