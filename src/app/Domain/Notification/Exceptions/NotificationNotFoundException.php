<?php

declare(strict_types=1);

namespace App\Domain\Notification\Exceptions;

final class NotificationNotFoundException extends \DomainException
{
    public static function withUuid(string $uuid): self
    {
        return new self("Notification with UUID [{$uuid}] not found.");
    }

    public static function withId(int $id): self
    {
        return new self("Notification with ID [{$id}] not found.");
    }
}
