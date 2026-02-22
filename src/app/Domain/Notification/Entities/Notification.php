<?php

declare(strict_types=1);

namespace App\Domain\Notification\Entities;

use App\Domain\Notification\Events\NotificationCreated;
use App\Domain\Notification\Events\NotificationStatusChanged;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use App\Domain\Notification\ValueObjects\NotificationId;
use App\Domain\Notification\ValueObjects\NotificationPriority;
use App\Domain\Notification\ValueObjects\NotificationStatus;
use App\Domain\Notification\ValueObjects\NotificationType;
use DateTimeImmutable;

final class Notification
{
    private array $domainEvents = [];

    private function __construct(
        private readonly NotificationId $id,
        private readonly string $uuid,
        private readonly int $userId,
        private readonly NotificationType $type,
        private readonly array $channels,
        private readonly array $payload,
        private readonly NotificationPriority $priority,
        private NotificationStatus $status,
        private readonly DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $updatedAt = null,
    ) {}

    public static function create(
        int $userId,
        NotificationType $type,
        array $channels,
        array $payload,
        NotificationPriority $priority,
    ): self {
        $notification = new self(
            id: NotificationId::generate(),
            uuid: \Ramsey\Uuid\Uuid::uuid4()->toString(),
            userId: $userId,
            type: $type,
            channels: $channels,
            payload: $payload,
            priority: $priority,
            status: NotificationStatus::pending(),
            createdAt: new DateTimeImmutable(),
        );

        $notification->recordEvent(new NotificationCreated($notification));

        return $notification;
    }

    public static function restore(
        NotificationId $id,
        string $uuid,
        int $userId,
        NotificationType $type,
        array $channels,
        array $payload,
        NotificationPriority $priority,
        NotificationStatus $status,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt = null,
    ): self {
        return new self(
            id: $id,
            uuid: $uuid,
            userId: $userId,
            type: $type,
            channels: $channels,
            payload: $payload,
            priority: $priority,
            status: $status,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function markAsProcessing(): void
    {
        $previousStatus = $this->status;
        $this->status = NotificationStatus::processing();
        $this->updatedAt = new DateTimeImmutable();
        $this->recordEvent(new NotificationStatusChanged($this, $previousStatus, $this->status));
    }

    public function markAsSent(): void
    {
        $previousStatus = $this->status;
        $this->status = NotificationStatus::sent();
        $this->updatedAt = new DateTimeImmutable();
        $this->recordEvent(new NotificationStatusChanged($this, $previousStatus, $this->status));
    }

    public function markAsFailed(string $reason = ''): void
    {
        $previousStatus = $this->status;
        $this->status = NotificationStatus::failed();
        $this->updatedAt = new DateTimeImmutable();
        $this->recordEvent(new NotificationStatusChanged($this, $previousStatus, $this->status));
    }

    public function markAsPartial(): void
    {
        $previousStatus = $this->status;
        $this->status = NotificationStatus::partial();
        $this->updatedAt = new DateTimeImmutable();
        $this->recordEvent(new NotificationStatusChanged($this, $previousStatus, $this->status));
    }

    public function getId(): NotificationId
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getType(): NotificationType
    {
        return $this->type;
    }

    public function getChannels(): array
    {
        return $this->channels;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getPriority(): NotificationPriority
    {
        return $this->priority;
    }

    public function getStatus(): NotificationStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }
}
