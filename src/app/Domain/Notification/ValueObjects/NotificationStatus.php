<?php

declare(strict_types=1);

namespace App\Domain\Notification\ValueObjects;

final readonly class NotificationStatus
{
    public const string PENDING    = 'pending';
    public const string PROCESSING = 'processing';
    public const string SENT       = 'sent';
    public const string FAILED     = 'failed';
    public const string PARTIAL    = 'partial';

    private const array VALID = [
        self::PENDING,
        self::PROCESSING,
        self::SENT,
        self::FAILED,
        self::PARTIAL,
    ];

    private function __construct(
        private string $value,
    ) {
        if (! in_array($value, self::VALID, true)) {
            throw new \InvalidArgumentException("Invalid notification status: {$value}");
        }
    }

    public static function pending(): self    { return new self(self::PENDING); }
    public static function processing(): self { return new self(self::PROCESSING); }
    public static function sent(): self       { return new self(self::SENT); }
    public static function failed(): self     { return new self(self::FAILED); }
    public static function partial(): self    { return new self(self::PARTIAL); }

    public static function from(string $value): self
    {
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isPending(): bool    { return $this->value === self::PENDING; }
    public function isProcessing(): bool { return $this->value === self::PROCESSING; }
    public function isSent(): bool       { return $this->value === self::SENT; }
    public function isFailed(): bool     { return $this->value === self::FAILED; }
    public function isPartial(): bool    { return $this->value === self::PARTIAL; }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
