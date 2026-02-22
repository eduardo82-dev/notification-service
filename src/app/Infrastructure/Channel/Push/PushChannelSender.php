<?php

declare(strict_types=1);

namespace App\Infrastructure\Channel\Push;

use App\Domain\Notification\Entities\Notification;
use App\Domain\Notification\Exceptions\ChannelSendException;
use App\Domain\Notification\Services\ChannelSenderInterface;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use Psr\Log\LoggerInterface;

final readonly class PushChannelSender implements ChannelSenderInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function supports(): string
    {
        return NotificationChannel::PUSH;
    }

    public function send(Notification $notification): void
    {
        $payload     = $notification->getPayload();
        $deviceToken = $payload['device_token'] ?? null;

        if (empty($deviceToken)) {
            throw new ChannelSendException(
                $this->supports(),
                "Missing 'device_token' in payload for notification [{$notification->getUuid()}]"
            );
        }

        // TODO: integrate FCM / APNs
        $this->logger->info('Push notification sent (stub)', [
            'uuid'         => $notification->getUuid(),
            'device_token' => substr($deviceToken, 0, 10) . '...',
        ]);
    }
}
