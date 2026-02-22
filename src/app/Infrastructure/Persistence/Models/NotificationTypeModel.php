<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property string $name
 * @property string $description
 */
final class NotificationTypeModel extends Model
{
    protected $table = 'notification_types';

    public $timestamps = false;

    protected $fillable = ['name', 'description'];
}
