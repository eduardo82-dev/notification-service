<?php

declare(strict_types=1);

namespace App\Infrastructure\Channel\Telegram;

use App\Domain\Notification\Entities\Notification;
use App\Domain\Notification\Exceptions\ChannelSendException;
use App\Domain\Notification\Services\ChannelSenderInterface;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;

final readonly class TelegramChannelSender implements ChannelSenderInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private string $botToken = '',
    ) {}

    public function supports(): string
    {
        return NotificationChannel::TELEGRAM;
    }

    public function send(Notification $notification): void
    {
        $payload  = $notification->getPayload();
        $chatId   = $payload['telegram_chat_id'] ?? null;

        if (empty($chatId)) {
            throw new ChannelSendException(
                $this->supports(),
                "Missing 'telegram_chat_id' in payload for notification [{$notification->getUuid()}]"
            );
        }

        if (empty($this->botToken)) {
            $this->logger->warning('Telegram bot token not configured — skipping (stub)', [
                'uuid' => $notification->getUuid(),
            ]);
            return;
        }

        try {
            $response = Http::post(
                "https://api.telegram.org/bot{$this->botToken}/sendMessage",
                [
                    'chat_id'    => $chatId,
                    'text'       => $this->buildMessage($notification),
                    'parse_mode' => 'HTML',
                ]
            );

            if (! $response->successful()) {
                throw new \RuntimeException($response->body());
            }

            $this->logger->info('Telegram notification sent', [
                'uuid'    => $notification->getUuid(),
                'chat_id' => $chatId,
            ]);
        } catch (\Throwable $e) {
            throw new ChannelSendException($this->supports(), $e->getMessage(), $e);
        }
    }

    private function buildMessage(Notification $notification): string
    {
        $type    = $notification->getType()->getValue();
        $payload = $notification->getPayload();
        $text    = "<b>" . ucwords(str_replace('_', ' ', $type)) . "</b>\n";

        foreach ($payload as $key => $value) {
            if ($key === 'telegram_chat_id') {
                continue;
            }
            $text .= ucfirst($key) . ': ' . (is_array($value) ? json_encode($value) : htmlspecialchars((string)$value)) . "\n";
        }

        return rtrim($text);
    }
}
