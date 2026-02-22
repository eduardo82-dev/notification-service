<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use App\Domain\Notification\Entities\Notification;
use App\Domain\Notification\Repositories\NotificationLogRepositoryInterface;
use App\Domain\Notification\Repositories\NotificationRepositoryInterface;
use App\Domain\Notification\Services\ChannelSenderInterface;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use Psr\Log\LoggerInterface;

final class ChannelDispatcher
{
    /** @var array<string, ChannelSenderInterface> */
    private array $senders = [];

    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository,
        private readonly NotificationLogRepositoryInterface $logRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function register(ChannelSenderInterface $sender): void
    {
        $this->senders[$sender->supports()] = $sender;
    }

    public function dispatch(Notification $notification): void
    {
        $results     = [];
        $hasSuccess  = false;
        $hasFailure  = false;

        $notification->markAsProcessing();
        $this->notificationRepository->save($notification);

        foreach ($notification->getChannels() as $channel) {
            /** @var NotificationChannel $channel */
            $channelName = $channel->getValue();
            $sender      = $this->senders[$channelName] ?? null;

            if ($sender === null) {
                $this->logger->warning("No sender registered for channel [{$channelName}]", [
                    'notification_uuid' => $notification->getUuid(),
                ]);
                $this->logRepository->log(
                    $notification->getId(),
                    $channelName,
                    'skipped',
                    "No sender registered for channel [{$channelName}]"
                );
                $hasFailure = true;
                continue;
            }

            try {
                $sender->send($notification);
                $this->logRepository->log($notification->getId(), $channelName, 'sent');
                $results[$channelName] = true;
                $hasSuccess = true;
            } catch (\Throwable $e) {
                $this->logger->error("Failed to send via [{$channelName}]", [
                    'notification_uuid' => $notification->getUuid(),
                    'error'             => $e->getMessage(),
                ]);
                $this->logRepository->log(
                    $notification->getId(),
                    $channelName,
                    'failed',
                    $e->getMessage()
                );
                $hasFailure = true;
            }
        }

        if ($hasSuccess && $hasFailure) {
            $notification->markAsPartial();
        } elseif ($hasSuccess) {
            $notification->markAsSent();
        } else {
            $notification->markAsFailed('All channels failed');
        }

        $this->notificationRepository->save($notification);
    }
}
