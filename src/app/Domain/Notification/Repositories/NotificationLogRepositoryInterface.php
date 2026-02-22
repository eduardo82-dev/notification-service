<?php

declare(strict_types=1);

namespace App\Domain\Notification\Repositories;

use App\Domain\Notification\ValueObjects\NotificationId;

interface NotificationLogRepositoryInterface
{
    public function log(
        NotificationId $notificationId,
        string $channel,
        string $status,
        ?string $errorMessage = null,
    ): void;

    public function findByNotificationId(NotificationId $notificationId): array;
}
