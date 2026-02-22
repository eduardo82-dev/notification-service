<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property string $name
 * @property string $description
 */
final class NotificationStatusModel extends Model
{
    protected $table = 'notification_statuses';

    public $timestamps = false;

    protected $fillable = ['name', 'description'];
}
