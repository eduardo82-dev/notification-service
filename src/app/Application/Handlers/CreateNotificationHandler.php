<?php

declare(strict_types=1);

namespace App\Application\Handlers;

use App\Application\Commands\CreateNotificationCommand;
use App\Application\DTOs\NotificationDTO;
use App\Domain\Notification\Entities\Notification;
use App\Domain\Notification\Repositories\NotificationRepositoryInterface;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use App\Domain\Notification\ValueObjects\NotificationPriority;
use App\Domain\Notification\ValueObjects\NotificationType;
use App\Domain\Outbox\Entities\OutboxMessage;
use App\Domain\Outbox\Repositories\OutboxRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class CreateNotificationHandler
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
        private OutboxRepositoryInterface $outboxRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function handle(CreateNotificationCommand $command): NotificationDTO
    {
        // Validate and convert incoming values to value objects. If any conversion fails,
        // rethrow as InvalidArgumentException so callers receive a 4xx error instead of 500.
        try {
            $channels = array_map(
                fn (string $ch) => NotificationChannel::from($ch),
                $command->channels
            );

            $type = NotificationType::from($command->type);
            $priority = NotificationPriority::from($command->priority);
        } catch (\ValueError | \TypeError $e) {
            $this->logger->warning('Invalid notification parameters', [
                'error' => $e->getMessage(),
                'user_id' => $command->userId,
            ]);

            throw new \InvalidArgumentException('Invalid notification parameters: ' . $e->getMessage(), 0, $e);
        }

        $notification = Notification::create(
            userId: $command->userId,
            type: $type,
            channels: $channels,
            payload: $command->payload,
            priority: $priority,
        );

        try {
            return DB::transaction(function () use ($notification): NotificationDTO {
            // 1. Persist the notification
            $savedNotification = $this->notificationRepository->save($notification);

            // 2. Write OUTBOX message atomically in the same transaction
            $outboxMessage = OutboxMessage::create(
                aggregateType: 'Notification',
                aggregateId: $savedNotification->getUuid(),
                eventType: 'notification.created',
                payload: [
                    'notification_id' => $savedNotification->getId()->getValue(),
                    'uuid'            => $savedNotification->getUuid(),
                    'user_id'         => $savedNotification->getUserId(),
                    'type'            => $savedNotification->getType()->getValue(),
                    'channels'        => array_map(
                        fn (NotificationChannel $ch) => $ch->getValue(),
                        $savedNotification->getChannels()
                    ),
                    'payload'         => $savedNotification->getPayload(),
                    'priority'        => $savedNotification->getPriority()->getValue(),
                ],
            );

            $savedOutbox = $this->outboxRepository->save($outboxMessage);

            $this->logger->info('Notification created and queued via OUTBOX', [
                'notification_uuid' => $savedNotification->getUuid(),
                'notification_id'   => $savedNotification->getId()->getValue(),
                'outbox_id'         => $savedOutbox->getId(),
                'user_id'           => $savedNotification->getUserId(),
                'type'              => $savedNotification->getType()->getValue(),
                'channels'          => array_map(
                    fn (NotificationChannel $ch) => $ch->getValue(),
                    $savedNotification->getChannels()
                ),
            ]);

            return NotificationDTO::fromEntity($savedNotification);
        });
        } catch (Throwable $e) {
            // Ensure we log context for failures and rethrow so higher layers can decide mapping.
            $this->logger->error('Failed to create notification', [
                'user_id' => $notification->getUserId(),
                'uuid' => $notification->getUuid(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
