<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\Jobs;

use App\Domain\Notification\Exceptions\NotificationNotFoundException;
use App\Domain\Notification\Repositories\NotificationRepositoryInterface;
use App\Infrastructure\Messaging\ChannelDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

final class ProcessNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 90;

    public function __construct(
        private readonly array $payload,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(
        NotificationRepositoryInterface $repository,
        ChannelDispatcher $dispatcher,
        LoggerInterface $logger,
    ): void {
        $uuid = $this->payload['uuid'] ?? null;

        if (! $uuid) {
            $logger->error('ProcessNotificationJob: missing uuid in payload', ['payload' => $this->payload]);
            return;
        }

        $notification = $repository->findByUuid($uuid);

        if ($notification === null) {
            throw NotificationNotFoundException::withUuid($uuid);
        }

        $logger->info('Processing notification', ['uuid' => $uuid]);
        $dispatcher->dispatch($notification);
    }

    public function failed(\Throwable $exception): void
    {
        app(LoggerInterface::class)->error('ProcessNotificationJob permanently failed', [
            'payload' => $this->payload,
            'error'   => $exception->getMessage(),
        ]);
    }
}
