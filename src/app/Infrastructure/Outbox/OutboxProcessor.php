<?php

declare(strict_types=1);

namespace App\Infrastructure\Outbox;

use App\Domain\Outbox\Repositories\OutboxRepositoryInterface;
use App\Infrastructure\Messaging\Jobs\ProcessNotificationJob;
use Illuminate\Contracts\Queue\Queue;
use Psr\Log\LoggerInterface;

final readonly class OutboxProcessor
{
    private const int MAX_ATTEMPTS = 5;
    private const int BATCH_SIZE   = 50;

    public function __construct(
        private OutboxRepositoryInterface $outboxRepository,
        private Queue $queue,
        private LoggerInterface $logger,
    ) {}

    public function process(): int
    {
        $messages = $this->outboxRepository->findUnprocessed(self::BATCH_SIZE);
        $count    = 0;

        foreach ($messages as $message) {
            if ($message->getAttempts() >= self::MAX_ATTEMPTS) {
                $this->logger->error('OutboxMessage exceeded max attempts, skipping', [
                    'id'         => $message->getId(),
                    'event_type' => $message->getEventType(),
                    'attempts'   => $message->getAttempts(),
                ]);
                // Mark as processed to prevent infinite loop; dead-letter handling
                $message->markAsProcessed();
                $this->outboxRepository->markAsProcessed($message);
                continue;
            }

            try {
                $message->incrementAttempts();
                $this->outboxRepository->update($message);

                $this->queue->pushOn(
                    'notifications',
                    new ProcessNotificationJob($message->getPayload())
                );

                $message->markAsProcessed();
                $this->outboxRepository->markAsProcessed($message);

                $count++;

                $this->logger->debug('OutboxMessage published to queue', [
                    'id'           => $message->getId(),
                    'aggregate_id' => $message->getAggregateId(),
                    'event_type'   => $message->getEventType(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to publish OutboxMessage', [
                    'id'    => $message->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }
}
