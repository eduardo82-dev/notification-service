<?php

declare(strict_types=1);

namespace App\Domain\Notification\Exceptions;

final class ChannelSendException extends \RuntimeException
{
    public function __construct(
        string $channel,
        string $reason,
        ?\Throwable $previous = null,
    ) {
        parent::__construct("Failed to send via [{$channel}]: {$reason}", 0, $previous);
    }
}
