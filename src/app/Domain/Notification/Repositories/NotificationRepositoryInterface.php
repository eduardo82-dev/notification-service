<?php

declare(strict_types=1);

namespace App\Domain\Notification\Repositories;

use App\Domain\Notification\Entities\Notification;
use App\Domain\Notification\ValueObjects\NotificationId;
use App\Domain\Notification\ValueObjects\NotificationStatus;

interface NotificationRepositoryInterface
{
    public function save(Notification $notification): Notification;

    public function findById(NotificationId $id): ?Notification;

    public function findByUuid(string $uuid): ?Notification;

    /**
     * @return Notification[]
     */
    public function findByUserId(int $userId, int $limit = 50, int $offset = 0): array;

    /**
     * @return Notification[]
     */
    public function findByStatus(NotificationStatus $status, int $limit = 100): array;

    public function countByUserId(int $userId): int;
}
