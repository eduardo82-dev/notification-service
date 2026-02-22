<?php

declare(strict_types=1);

use App\Domain\Notification\Entities\Notification;
use App\Domain\Notification\Events\NotificationCreated;
use App\Domain\Notification\Events\NotificationStatusChanged;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use App\Domain\Notification\ValueObjects\NotificationPriority;
use App\Domain\Notification\ValueObjects\NotificationStatus;
use App\Domain\Notification\ValueObjects\NotificationType;

describe('Notification Entity', function () {
    it('creates a notification with pending status', function () {
        $notification = Notification::create(
            userId: 15,
            type: NotificationType::from('order_paid'),
            channels: [NotificationChannel::from('email')],
            payload: ['order_id' => 1001],
            priority: NotificationPriority::high(),
        );

        expect($notification->getStatus()->isPending())->toBeTrue()
            ->and($notification->getUserId())->toBe(15)
            ->and($notification->getType()->getValue())->toBe('order_paid')
            ->and($notification->getPriority()->getValue())->toBe('high');
    });

    it('records NotificationCreated domain event on creation', function () {
        $notification = Notification::create(
            userId: 1,
            type: NotificationType::from('test_event'),
            channels: [NotificationChannel::from('sms')],
            payload: [],
            priority: NotificationPriority::normal(),
        );

        $events = $notification->releaseEvents();

        expect($events)->toHaveCount(1)
            ->and($events[0])->toBeInstanceOf(NotificationCreated::class);
    });

    it('transitions status and emits domain event', function () {
        $notification = Notification::create(
            userId: 1,
            type: NotificationType::from('test'),
            channels: [NotificationChannel::from('email')],
            payload: [],
            priority: NotificationPriority::low(),
        );
        $notification->releaseEvents(); // clear creation event

        $notification->markAsSent();
        $events = $notification->releaseEvents();

        expect($notification->getStatus()->isSent())->toBeTrue()
            ->and($events)->toHaveCount(1)
            ->and($events[0])->toBeInstanceOf(NotificationStatusChanged::class)
            ->and($events[0]->newStatus->isSent())->toBeTrue();
    });

    it('marks partial when some channels succeed', function () {
        $notification = Notification::create(
            userId: 1,
            type: NotificationType::from('test'),
            channels: [NotificationChannel::from('email'), NotificationChannel::from('sms')],
            payload: [],
            priority: NotificationPriority::normal(),
        );

        $notification->markAsPartial();

        expect($notification->getStatus()->isPartial())->toBeTrue();
    });
});

describe('NotificationPriority ValueObject', function () {
    it('correctly compares priorities', function () {
        $high   = NotificationPriority::high();
        $normal = NotificationPriority::normal();
        $low    = NotificationPriority::low();

        expect($high->isHigherThan($normal))->toBeTrue()
            ->and($high->isHigherThan($low))->toBeTrue()
            ->and($normal->isHigherThan($low))->toBeTrue()
            ->and($low->isHigherThan($high))->toBeFalse();
    });

    it('throws on invalid priority', function () {
        expect(fn () => NotificationPriority::from('invalid'))
            ->toThrow(\InvalidArgumentException::class);
    });
});

describe('NotificationType ValueObject', function () {
    it('accepts valid snake_case types', function () {
        $type = NotificationType::from('order_paid');
        expect($type->getValue())->toBe('order_paid');
    });

    it('rejects invalid type names', function () {
        expect(fn () => NotificationType::from('Order-Paid'))
            ->toThrow(\InvalidArgumentException::class);

        expect(fn () => NotificationType::from(''))
            ->toThrow(\InvalidArgumentException::class);
    });
});
