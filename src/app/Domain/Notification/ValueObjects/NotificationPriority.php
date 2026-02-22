<?php

declare(strict_types=1);

namespace App\Domain\Notification\ValueObjects;

final readonly class NotificationPriority
{
    public const string LOW    = 'low';
    public const string NORMAL = 'normal';
    public const string HIGH   = 'high';

    private const array WEIGHT = [
        self::LOW    => 1,
        self::NORMAL => 5,
        self::HIGH   => 10,
    ];

    private function __construct(
        private string $value,
    ) {
        if (! array_key_exists($value, self::WEIGHT)) {
            throw new \InvalidArgumentException("Invalid priority: {$value}");
        }
    }

    public static function low(): self    { return new self(self::LOW); }
    public static function normal(): self { return new self(self::NORMAL); }
    public static function high(): self   { return new self(self::HIGH); }

    public static function from(string $value): self
    {
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getWeight(): int
    {
        return self::WEIGHT[$this->value];
    }

    public function isHigherThan(self $other): bool
    {
        return $this->getWeight() > $other->getWeight();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
