<?php

declare(strict_types=1);

use App\Application\Commands\CreateNotificationCommand;
use App\Application\Handlers\CreateNotificationHandler;
use App\Domain\Notification\Entities\Notification;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use App\Domain\Notification\ValueObjects\NotificationPriority;
use App\Domain\Notification\ValueObjects\NotificationType;
use App\Domain\Outbox\Entities\OutboxMessage;
use App\Domain\Notification\Repositories\NotificationRepositoryInterface;
use App\Domain\Outbox\Repositories\OutboxRepositoryInterface;
use Psr\Log\LoggerInterface;

it('creates notification and writes outbox message', function () {
    $notificationRepo = Mockery::mock(NotificationRepositoryInterface::class);
    $outboxRepo = Mockery::mock(OutboxRepositoryInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldIgnoreMissing();

    $handler = new CreateNotificationHandler($notificationRepo, $outboxRepo, $logger);

    $command = new CreateNotificationCommand(
        userId: 10,
        type: 'order_created',
        channels: ['email'],
        payload: ['order_id' => 123],
        priority: 'normal'
    );

    $notification = Notification::create(
        10,
        NotificationType::from('order_created'),
        [NotificationChannel::from('email')],
        ['order_id' => 123],
        NotificationPriority::from('normal')
    );

    $notificationRepo->shouldReceive('save')
        ->once()
        ->with(Mockery::on(fn($arg) => $arg instanceof Notification))
        ->andReturn($notification);

    $outbox = OutboxMessage::create(
        'Notification',
        $notification->getUuid(),
        'notification.created',
        ['notification_id' => $notification->getId()->getValue()]
    );

    $outboxRepo->shouldReceive('save')
        ->once()
        ->andReturn($outbox);

    $dto = $handler->handle($command);

    expect($dto)->toBeObject();
    expect($dto->userId)->toBe(10);
    expect($dto->type)->toBe('order_created');
    expect($dto->status)->toBe('pending');
});

it('throws InvalidArgumentException for invalid enum values', function () {
    $notificationRepo = Mockery::mock(NotificationRepositoryInterface::class);
    $outboxRepo = Mockery::mock(OutboxRepositoryInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldIgnoreMissing();

    $handler = new CreateNotificationHandler($notificationRepo, $outboxRepo, $logger);

    $command = new CreateNotificationCommand(
        userId: 1,
        type: 'InvalidType!',
        channels: ['email'],
        payload: [],
        priority: 'normal'
    );

    expect(fn () => $handler->handle($command))->toThrow(InvalidArgumentException::class);
});
