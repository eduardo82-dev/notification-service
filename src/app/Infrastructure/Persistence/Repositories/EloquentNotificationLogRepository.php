<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Notification\Repositories\NotificationLogRepositoryInterface;
use App\Domain\Notification\ValueObjects\NotificationId;
use App\Infrastructure\Persistence\Models\NotificationLogModel;

final class EloquentNotificationLogRepository implements NotificationLogRepositoryInterface
{
    public function log(
        NotificationId $notificationId,
        string $channel,
        string $status,
        ?string $errorMessage = null,
    ): void {
        NotificationLogModel::create([
            'notification_id' => $notificationId->getValue(),
            'channel'         => $channel,
            'status'          => $status,
            'error_message'   => $errorMessage,
            'created_at'      => now(),
        ]);
    }

    public function findByNotificationId(NotificationId $notificationId): array
    {
        return NotificationLogModel::where('notification_id', $notificationId->getValue())
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }
}
