<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Notification\Repositories\NotificationLogRepositoryInterface;
use App\Domain\Notification\Repositories\NotificationRepositoryInterface;
use App\Domain\Notification\Services\ChannelSenderInterface;
use App\Domain\Outbox\Repositories\OutboxRepositoryInterface;
use App\Infrastructure\Channel\Email\EmailChannelSender;
use App\Infrastructure\Channel\Push\PushChannelSender;
use App\Infrastructure\Channel\Sms\SmsChannelSender;
use App\Infrastructure\Channel\Telegram\TelegramChannelSender;
use App\Infrastructure\Messaging\ChannelDispatcher;
use App\Infrastructure\Persistence\Repositories\EloquentNotificationLogRepository;
use App\Infrastructure\Persistence\Repositories\EloquentNotificationRepository;
use App\Infrastructure\Persistence\Repositories\EloquentOutboxRepository;
use Illuminate\Support\ServiceProvider;

final class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Domain → Infrastructure bindings
        $this->app->bind(NotificationRepositoryInterface::class, EloquentNotificationRepository::class);
        $this->app->bind(NotificationLogRepositoryInterface::class, EloquentNotificationLogRepository::class);
        $this->app->bind(OutboxRepositoryInterface::class, EloquentOutboxRepository::class);

        // Channel Dispatcher (singleton with all senders registered)
        $this->app->singleton(ChannelDispatcher::class, function ($app) {
            $dispatcher = new ChannelDispatcher(
                notificationRepository: $app->make(NotificationRepositoryInterface::class),
                logRepository: $app->make(NotificationLogRepositoryInterface::class),
                logger: $app->make(\Psr\Log\LoggerInterface::class),
            );

            // Register all channel senders
            $dispatcher->register($app->make(EmailChannelSender::class));
            $dispatcher->register($app->make(SmsChannelSender::class));
            $dispatcher->register(new TelegramChannelSender(
                logger: $app->make(\Psr\Log\LoggerInterface::class),
                botToken: config('services.telegram.bot_token', ''),
            ));
            $dispatcher->register($app->make(PushChannelSender::class));

            return $dispatcher;
        });
    }

    public function boot(): void
    {
        //
    }
}
