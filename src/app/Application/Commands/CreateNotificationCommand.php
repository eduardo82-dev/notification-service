<?php

declare(strict_types=1);

namespace App\Application\Commands;

final readonly class CreateNotificationCommand
{
    /**
     * @param string[] $channels
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $type,
        public readonly array $channels,
        public readonly array $payload,
        public readonly string $priority = 'normal',
    ) {}
}
