<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Commands\CreateNotificationCommand;
use App\Application\Handlers\CreateNotificationHandler;
use App\Domain\Notification\Exceptions\NotificationNotFoundException;
use App\Domain\Notification\Repositories\NotificationRepositoryInterface;
use App\Http\Requests\CreateNotificationRequest;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'Unified notification service supporting email, SMS, Telegram, push and more. Implements OUTBOX pattern for reliable delivery.',
    title: 'Notification Service API',
    contact: new OA\Contact(email: 'api@notifications.local'),
    license: new OA\License(name: 'MIT'),
)]
#[OA\Server(
    url: 'http://localhost:8081',
    description: 'Local development server',
)]
#[OA\Tag(name: 'Notifications', description: 'Notification management endpoints')]
#[OA\Tag(name: 'Health', description: 'Health check endpoints')]
#[OA\Schema(
    schema: 'NotificationPayload',
    description: "Flexible key-value payload. Fields depend on channel: 'email' for email, 'phone' for SMS, 'telegram_chat_id' for Telegram.",
    type: 'object',
)]
#[OA\Schema(
    schema: 'NotificationResponse',
    properties: [
        new OA\Property(property: 'id',         type: 'integer', example: 1),
        new OA\Property(property: 'uuid',        type: 'string',  format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
        new OA\Property(property: 'user_id',     type: 'integer', example: 15),
        new OA\Property(property: 'type',        type: 'string',  example: 'order_paid'),
        new OA\Property(property: 'channels',    type: 'array',   items: new OA\Items(type: 'string', example: 'email')),
        new OA\Property(property: 'payload',     ref: '#/components/schemas/NotificationPayload'),
        new OA\Property(property: 'priority',    type: 'string',  enum: ['low', 'normal', 'high'], example: 'high'),
        new OA\Property(property: 'status',      type: 'string',  enum: ['pending', 'processing', 'sent', 'failed', 'partial'], example: 'pending'),
        new OA\Property(property: 'created_at',  type: 'string',  format: 'date-time'),
        new OA\Property(property: 'updated_at',  type: 'string',  format: 'date-time', nullable: true),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    properties: [
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(property: 'errors',  type: 'object', nullable: true),
    ],
    type: 'object',
)]
final class NotificationController extends Controller
{
    public function __construct(
        private readonly CreateNotificationHandler $createHandler,
        private readonly NotificationRepositoryInterface $notificationRepository,
    ) {}

    #[OA\Post(
        path: '/api/v1/notifications',
        operationId: 'createNotification',
        description: 'Atomically persists a notification and writes an OUTBOX message. The OUTBOX relay then publishes to RabbitMQ for async channel delivery.',
        summary: 'Create and queue a new notification',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'type', 'channels', 'payload'],
                properties: [
                    new OA\Property(property: 'user_id',  type: 'integer', example: 15),
                    new OA\Property(property: 'type',     type: 'string',  example: 'order_paid', description: 'Snake-case event type'),
                    new OA\Property(
                        property: 'channels',
                        type: 'array',
                        items: new OA\Items(type: 'string', enum: ['email', 'sms', 'telegram', 'push', 'webhook']),
                        example: ['email', 'sms'],
                    ),
                    new OA\Property(property: 'priority', type: 'string', enum: ['low', 'normal', 'high'], example: 'high'),
                    new OA\Property(property: 'payload',  type: 'object', example: ['order_id' => 1001]),
                ],
            ),
        ),
        tags: ['Notifications'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Notification created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/NotificationResponse'),
                    ],
                ),
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 500,
                description: 'Internal server error',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function store(CreateNotificationRequest $request): JsonResponse
    {
        $command = new CreateNotificationCommand(
            userId: $request->integer('user_id'),
            type: $request->string('type')->toString(),
            channels: $request->input('channels'),
            payload: $request->input('payload', []),
            priority: $request->input('priority', 'normal'),
        );

        $dto = $this->createHandler->handle($command);

        return response()->json(['data' => $dto->toArray()], 201);
    }

    #[OA\Get(
        path: '/api/v1/notifications/{uuid}',
        operationId: 'getNotification',
        summary: 'Get a notification by UUID',
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notification details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/NotificationResponse'),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function show(string $uuid): JsonResponse
    {
        $notification = $this->notificationRepository->findByUuid($uuid);

        if ($notification === null) {
            throw NotificationNotFoundException::withUuid($uuid);
        }

        return response()->json(['data' => (new NotificationResource($notification))->toArray(request())]);
    }

    #[OA\Get(
        path: '/api/v1/users/{userId}/notifications',
        operationId: 'listUserNotifications',
        summary: 'List notifications for a user',
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path',  required: true,  schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'limit',  in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
            new OA\Parameter(name: 'offset', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 0)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of notifications',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/NotificationResponse'),
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'total',  type: 'integer'),
                                new OA\Property(property: 'limit',  type: 'integer'),
                                new OA\Property(property: 'offset', type: 'integer'),
                            ],
                            type: 'object',
                        ),
                    ],
                ),
            ),
        ],
    )]
    public function userNotifications(Request $request, int $userId): JsonResponse
    {
        $limit  = min((int) $request->query('limit', 20), 100);
        $offset = max((int) $request->query('offset', 0), 0);

        $notifications = $this->notificationRepository->findByUserId($userId, $limit, $offset);
        $total         = $this->notificationRepository->countByUserId($userId);

        return response()->json([
            'data' => array_map(
                fn ($n) => (new NotificationResource($n))->toArray($request),
                $notifications
            ),
            'meta' => [
                'total'  => $total,
                'limit'  => $limit,
                'offset' => $offset,
            ],
        ]);
    }
}