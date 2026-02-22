<?php

declare(strict_types=1);

namespace App\Domain\Notification\Events;

use App\Domain\Notification\Entities\Notification;
use App\Domain\Notification\ValueObjects\NotificationStatus;

final readonly class NotificationStatusChanged
{
    public function __construct(
        public Notification $notification,
        public NotificationStatus $previousStatus,
        public NotificationStatus $newStatus,
    ) {}
}
