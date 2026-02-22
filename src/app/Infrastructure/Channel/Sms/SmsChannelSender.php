<?php

declare(strict_types=1);

namespace App\Infrastructure\Channel\Sms;

use App\Domain\Notification\Entities\Notification;
use App\Domain\Notification\Exceptions\ChannelSendException;
use App\Domain\Notification\Services\ChannelSenderInterface;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use Psr\Log\LoggerInterface;

final readonly class SmsChannelSender implements ChannelSenderInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function supports(): string
    {
        return NotificationChannel::SMS;
    }

    public function send(Notification $notification): void
    {
        $payload = $notification->getPayload();
        $phone   = $payload['phone'] ?? null;

        if (empty($phone)) {
            throw new ChannelSendException(
                $this->supports(),
                "Missing 'phone' in payload for notification [{$notification->getUuid()}]"
            );
        }

        try {
            // TODO: integrate real SMS provider (Twilio, Vonage, etc.)
            // Example: app(TwilioClient::class)->messages->create($phone, [...])

            $this->logger->info('SMS notification sent (stub)', [
                'uuid'  => $notification->getUuid(),
                'phone' => $phone,
                'type'  => $notification->getType()->getValue(),
            ]);
        } catch (\Throwable $e) {
            throw new ChannelSendException($this->supports(), $e->getMessage(), $e);
        }
    }
}
