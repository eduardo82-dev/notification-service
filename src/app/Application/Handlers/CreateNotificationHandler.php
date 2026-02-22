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

final readonly class CreateNotificationHandler
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
        private OutboxRepositoryInterface $outboxRepository,
        private LoggerInterface $logger,
    ) {}

    public function handle(CreateNotificationCommand $command): NotificationDTO
    {
        $channels = array_map(
            fn (string $ch) => NotificationChannel::from($ch),
            $command->channels
        );

        $notification = Notification::create(
            userId: $command->userId,
            type: NotificationType::from($command->type),
            channels: $channels,
            payload: $command->payload,
            priority: NotificationPriority::from($command->priority),
        );

        return DB::transaction(function () use ($notification, $command): NotificationDTO {
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

            $this->outboxRepository->save($outboxMessage);

            $this->logger->info('Notification created and queued via OUTBOX', [
                'notification_uuid' => $savedNotification->getUuid(),
                'user_id'           => $savedNotification->getUserId(),
                'type'              => $savedNotification->getType()->getValue(),
                'channels'          => array_map(
                    fn (NotificationChannel $ch) => $ch->getValue(),
                    $savedNotification->getChannels()
                ),
            ]);

            return NotificationDTO::fromEntity($savedNotification);
        });
    }
}
