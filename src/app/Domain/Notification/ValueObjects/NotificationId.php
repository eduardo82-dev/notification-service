<?php

declare(strict_types=1);

namespace App\Domain\Notification\ValueObjects;

final readonly class NotificationId
{
    private function __construct(
        private int $value,
    ) {
        if ($value <= 0) {
            throw new \InvalidArgumentException("NotificationId must be a positive integer, got: {$value}");
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public static function generate(): self
    {
        // Will be set after persistence; use 0 as placeholder before save
        return new self(PHP_INT_MAX); // sentinel — replaced by DB auto-increment
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
