<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Notification\ValueObjects\NotificationChannel;
use App\Domain\Notification\ValueObjects\NotificationPriority;
use Illuminate\Foundation\Http\FormRequest;

final class CreateNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validChannels  = implode(',', NotificationChannel::supportedChannels());
        $validPriorities = implode(',', [
            NotificationPriority::LOW,
            NotificationPriority::NORMAL,
            NotificationPriority::HIGH,
        ]);

        return [
            'user_id'    => ['required', 'integer', 'min:1'],
            'type'       => ['required', 'string', 'regex:/^[a-z][a-z0-9_]*$/', 'max:100'],
            'channels'   => ['required', 'array', 'min:1'],
            'channels.*' => ['required', 'string', "in:{$validChannels}"],
            'priority'   => ['sometimes', 'string', "in:{$validPriorities}"],
            'payload'    => ['required', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.regex'       => 'The type must be a snake_case string (e.g. order_paid).',
            'channels.in'      => 'Unsupported channel. Supported: ' . implode(', ', NotificationChannel::supportedChannels()),
            'channels.*.in'    => 'Unsupported channel. Supported: ' . implode(', ', NotificationChannel::supportedChannels()),
        ];
    }
}
