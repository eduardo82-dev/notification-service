<?php

declare(strict_types=1);

namespace App\Infrastructure\Channel\Email;

use App\Domain\Notification\Entities\Notification;
use App\Domain\Notification\Exceptions\ChannelSendException;
use App\Domain\Notification\Services\ChannelSenderInterface;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use Illuminate\Support\Facades\Mail;
use Psr\Log\LoggerInterface;

final readonly class EmailChannelSender implements ChannelSenderInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function supports(): string
    {
        return NotificationChannel::EMAIL;
    }

    public function send(Notification $notification): void
    {
        $payload = $notification->getPayload();

        // Resolve email address: from payload or fallback
        $email = $payload['email'] ?? $payload['to'] ?? null;

        if (empty($email)) {
            throw new ChannelSendException(
                $this->supports(),
                "Missing 'email' or 'to' in payload for notification [{$notification->getUuid()}]"
            );
        }

        try {
            Mail::raw(
                $this->buildMessage($notification),
                function ($message) use ($email, $notification) {
                    $message->to($email)
                        ->subject($this->buildSubject($notification));
                }
            );

            $this->logger->info('Email notification sent', [
                'uuid'  => $notification->getUuid(),
                'to'    => $email,
                'type'  => $notification->getType()->getValue(),
            ]);
        } catch (\Throwable $e) {
            throw new ChannelSendException($this->supports(), $e->getMessage(), $e);
        }
    }

    private function buildSubject(Notification $notification): string
    {
        return $notification->getPayload()['subject']
            ?? ucwords(str_replace('_', ' ', $notification->getType()->getValue()));
    }

    private function buildMessage(Notification $notification): string
    {
        $payload = $notification->getPayload();
        $lines   = ["Notification type: {$notification->getType()->getValue()}"];

        foreach ($payload as $key => $value) {
            if (in_array($key, ['email', 'to', 'subject'], true)) {
                continue;
            }
            $lines[] = ucfirst($key) . ': ' . (is_array($value) ? json_encode($value) : $value);
        }

        return implode("\n", $lines);
    }
}
