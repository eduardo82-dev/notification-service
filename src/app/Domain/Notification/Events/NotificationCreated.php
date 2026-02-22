<?php

declare(strict_types=1);

namespace App\Domain\Notification\Events;

use App\Domain\Notification\Entities\Notification;

final readonly class NotificationCreated
{
    public function __construct(
        public Notification $notification,
    ) {}
}
