<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Notification\Entities\Notification;
use App\Domain\Notification\Repositories\NotificationRepositoryInterface;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use App\Domain\Notification\ValueObjects\NotificationId;
use App\Domain\Notification\ValueObjects\NotificationPriority;
use App\Domain\Notification\ValueObjects\NotificationStatus;
use App\Domain\Notification\ValueObjects\NotificationType;
use App\Infrastructure\Persistence\Models\NotificationModel;
use App\Infrastructure\Persistence\Models\NotificationStatusModel;
use App\Infrastructure\Persistence\Models\NotificationTypeModel;

final class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function save(Notification $notification): Notification
    {
        $typeModel = NotificationTypeModel::firstOrCreate(
            ['name' => $notification->getType()->getValue()],
            ['description' => '']
        );

        $statusModel = NotificationStatusModel::firstOrCreate(
            ['name' => $notification->getStatus()->getValue()],
            ['description' => '']
        );

        $channels = array_map(
            fn (NotificationChannel $ch) => $ch->getValue(),
            $notification->getChannels()
        );

        $model = NotificationModel::updateOrCreate(
            ['uuid' => $notification->getUuid()],
            [
                'user_id'   => $notification->getUserId(),
                'type_id'   => $typeModel->id,
                'status_id' => $statusModel->id,
                'payload'   => $notification->getPayload(),
                'priority'  => $notification->getPriority()->getValue(),
                'channels'  => $channels,
            ]
        );

        return $this->toDomain($model);
    }

    public function findById(NotificationId $id): ?Notification
    {
        $model = NotificationModel::with(['type', 'status'])->find($id->getValue());
        return $model ? $this->toDomain($model) : null;
    }

    public function findByUuid(string $uuid): ?Notification
    {
        $model = NotificationModel::with(['type', 'status'])->where('uuid', $uuid)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function findByUserId(int $userId, int $limit = 50, int $offset = 0): array
    {
        return NotificationModel::with(['type', 'status'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(fn (NotificationModel $m) => $this->toDomain($m))
            ->all();
    }

    public function findByStatus(NotificationStatus $status, int $limit = 100): array
    {
        return NotificationModel::with(['type', 'status'])
            ->whereHas('status', fn ($q) => $q->where('name', $status->getValue()))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (NotificationModel $m) => $this->toDomain($m))
            ->all();
    }

    public function countByUserId(int $userId): int
    {
        return NotificationModel::where('user_id', $userId)->count();
    }

    private function toDomain(NotificationModel $model): Notification
    {
        $channels = array_map(
            fn (string $ch) => NotificationChannel::from($ch),
            $model->channels ?? []
        );

        return Notification::restore(
            id: NotificationId::fromInt($model->id),
            uuid: $model->uuid,
            userId: $model->user_id,
            type: NotificationType::from($model->type->name),
            channels: $channels,
            payload: $model->payload ?? [],
            priority: NotificationPriority::from($model->priority),
            status: NotificationStatus::from($model->status->name),
            createdAt: new \DateTimeImmutable($model->created_at->toIso8601String()),
            updatedAt: $model->updated_at
                ? new \DateTimeImmutable($model->updated_at->toIso8601String())
                : null,
        );
    }
}
