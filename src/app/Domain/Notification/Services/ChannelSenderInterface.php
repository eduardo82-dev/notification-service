<?php

declare(strict_types=1);

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Entities\Notification;

interface ChannelSenderInterface
{
    /**
     * Returns the channel name this sender handles.
     */
    public function supports(): string;

    /**
     * @throws \App\Domain\Notification\Exceptions\ChannelSendException
     */
    public function send(Notification $notification): void;
}
