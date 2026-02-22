<?php

declare(strict_types=1);

namespace App\Domain\Outbox\Repositories;

use App\Domain\Outbox\Entities\OutboxMessage;

interface OutboxRepositoryInterface
{
    public function save(OutboxMessage $message): OutboxMessage;

    /**
     * @return OutboxMessage[]
     */
    public function findUnprocessed(int $limit = 50): array;

    public function markAsProcessed(OutboxMessage $message): void;

    public function update(OutboxMessage $message): void;
}
