<?php

declare(strict_types=1);

namespace App\Domain\Notification\ValueObjects;

final readonly class NotificationType
{
    private function __construct(
        private string $value,
    ) {
        if (empty(trim($value))) {
            throw new \InvalidArgumentException('Notification type cannot be empty');
        }

        if (! preg_match('/^[a-z][a-z0-9_]*$/', $value)) {
            throw new \InvalidArgumentException(
                "Notification type must be snake_case, got: {$value}"
            );
        }
    }

    public static function from(string $value): self
    {
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
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
