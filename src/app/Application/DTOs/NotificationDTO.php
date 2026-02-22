<?php

declare(strict_types=1);

namespace App\Application\DTOs;

use App\Domain\Notification\Entities\Notification;
use App\Domain\Notification\ValueObjects\NotificationChannel;

final readonly class NotificationDTO
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $userId,
        public string $type,
        public array $channels,
        public array $payload,
        public string $priority,
        public string $status,
        public string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fromEntity(Notification $notification): self
    {
        return new self(
            id: $notification->getId()->getValue(),
            uuid: $notification->getUuid(),
            userId: $notification->getUserId(),
            type: $notification->getType()->getValue(),
            channels: array_map(
                fn (NotificationChannel $ch) => $ch->getValue(),
                $notification->getChannels()
            ),
            payload: $notification->getPayload(),
            priority: $notification->getPriority()->getValue(),
            status: $notification->getStatus()->getValue(),
            createdAt: $notification->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $notification->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        );
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'uuid'       => $this->uuid,
            'user_id'    => $this->userId,
            'type'       => $this->type,
            'channels'   => $this->channels,
            'payload'    => $this->payload,
            'priority'   => $this->priority,
            'status'     => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
