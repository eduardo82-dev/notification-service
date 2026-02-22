<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Notification\Entities\Notification;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Notification
 */
final class NotificationResource extends JsonResource
{
    public function __construct(
        private readonly Notification $notification,
    ) {
        parent::__construct($notification);
    }

    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->notification->getId()->getValue(),
            'uuid'       => $this->notification->getUuid(),
            'user_id'    => $this->notification->getUserId(),
            'type'       => $this->notification->getType()->getValue(),
            'channels'   => array_map(
                fn (NotificationChannel $ch) => $ch->getValue(),
                $this->notification->getChannels()
            ),
            'payload'    => $this->notification->getPayload(),
            'priority'   => $this->notification->getPriority()->getValue(),
            'status'     => $this->notification->getStatus()->getValue(),
            'created_at' => $this->notification->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $this->notification->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
