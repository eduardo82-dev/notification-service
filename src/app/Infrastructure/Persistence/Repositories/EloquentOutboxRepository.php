<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Outbox\Entities\OutboxMessage;
use App\Domain\Outbox\Repositories\OutboxRepositoryInterface;
use App\Infrastructure\Persistence\Models\OutboxMessageModel;

final class EloquentOutboxRepository implements OutboxRepositoryInterface
{
    public function save(OutboxMessage $message): OutboxMessage
    {
        $model = OutboxMessageModel::create([
            'aggregate_type' => $message->getAggregateType(),
            'aggregate_id'   => $message->getAggregateId(),
            'event_type'     => $message->getEventType(),
            'payload'        => $message->getPayload(),
            'processed'      => $message->isProcessed(),
            'attempts'       => $message->getAttempts(),
            'created_at'     => $message->getCreatedAt(),
            'processed_at'   => $message->getProcessedAt(),
        ]);

        return $this->toDomain($model);
    }

    public function findUnprocessed(int $limit = 50): array
    {
        return OutboxMessageModel::where('processed', false)
            ->orderBy('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (OutboxMessageModel $m) => $this->toDomain($m))
            ->all();
    }

    public function markAsProcessed(OutboxMessage $message): void
    {
        OutboxMessageModel::where('id', $message->getId())
            ->update([
                'processed'    => true,
                'processed_at' => now(),
                'attempts'     => $message->getAttempts(),
            ]);
    }

    public function update(OutboxMessage $message): void
    {
        OutboxMessageModel::where('id', $message->getId())
            ->update([
                'processed'    => $message->isProcessed(),
                'processed_at' => $message->getProcessedAt(),
                'attempts'     => $message->getAttempts(),
            ]);
    }

    private function toDomain(OutboxMessageModel $model): OutboxMessage
    {
        $reflection = new \ReflectionClass(OutboxMessage::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        $props = [
            'id'            => $model->id,
            'aggregateType' => $model->aggregate_type,
            'aggregateId'   => $model->aggregate_id,
            'eventType'     => $model->event_type,
            'payload'       => $model->payload ?? [],
            'processed'     => $model->processed,
            'createdAt'     => new \DateTimeImmutable($model->created_at->toIso8601String()),
            'processedAt'   => $model->processed_at
                ? new \DateTimeImmutable($model->processed_at->toIso8601String())
                : null,
            'attempts'      => $model->attempts,
        ];

        foreach ($props as $propName => $value) {
            $prop = $reflection->getProperty($propName);
            $prop->setAccessible(true);
            $prop->setValue($instance, $value);
        }

        return $instance;
    }
}
