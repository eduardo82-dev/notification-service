<?php

declare(strict_types=1);

namespace App\Domain\Outbox\Entities;

use DateTimeImmutable;

final class OutboxMessage
{
    public function __construct(
        private ?int $id,
        private readonly string $aggregateType,
        private readonly string $aggregateId,
        private readonly string $eventType,
        private readonly array $payload,
        private bool $processed,
        private readonly DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $processedAt = null,
        private int $attempts = 0,
    ) {}

    public static function create(
        string $aggregateType,
        string $aggregateId,
        string $eventType,
        array $payload,
    ): self {
        return new self(
            id: null,
            aggregateType: $aggregateType,
            aggregateId: $aggregateId,
            eventType: $eventType,
            payload: $payload,
            processed: false,
            createdAt: new DateTimeImmutable(),
        );
    }

    public function markAsProcessed(): void
    {
        $this->processed = true;
        $this->processedAt = new DateTimeImmutable();
    }

    public function incrementAttempts(): void
    {
        ++$this->attempts;
    }

    public function getId(): ?int                       { return $this->id; }
    public function getAggregateType(): string          { return $this->aggregateType; }
    public function getAggregateId(): string            { return $this->aggregateId; }
    public function getEventType(): string              { return $this->eventType; }
    public function getPayload(): array                 { return $this->payload; }
    public function isProcessed(): bool                 { return $this->processed; }
    public function getCreatedAt(): DateTimeImmutable   { return $this->createdAt; }
    public function getProcessedAt(): ?DateTimeImmutable{ return $this->processedAt; }
    public function getAttempts(): int                  { return $this->attempts; }
}
