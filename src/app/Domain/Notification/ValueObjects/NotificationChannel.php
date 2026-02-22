<?php

declare(strict_types=1);

namespace App\Domain\Notification\ValueObjects;

final readonly class NotificationChannel
{
    public const string EMAIL    = 'email';
    public const string SMS      = 'sms';
    public const string TELEGRAM = 'telegram';
    public const string PUSH     = 'push';
    public const string WEBHOOK  = 'webhook';

    private const array SUPPORTED = [
        self::EMAIL,
        self::SMS,
        self::TELEGRAM,
        self::PUSH,
        self::WEBHOOK,
    ];

    private function __construct(
        private string $value,
    ) {
        // Allow any non-empty string so new channels can be added without code changes
        if (empty(trim($value))) {
            throw new \InvalidArgumentException('Channel cannot be empty');
        }
    }

    public static function from(string $value): self
    {
        return new self(strtolower(trim($value)));
    }

    public static function supportedChannels(): array
    {
        return self::SUPPORTED;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isSupported(): bool
    {
        return in_array($this->value, self::SUPPORTED, true);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
