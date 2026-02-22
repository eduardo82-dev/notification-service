<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Models\NotificationStatusModel;
use App\Infrastructure\Persistence\Models\NotificationTypeModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed required lookup tables
    $statuses = ['pending', 'processing', 'sent', 'failed', 'partial'];
    foreach ($statuses as $status) {
        NotificationStatusModel::firstOrCreate(['name' => $status], ['description' => '']);
    }
});

describe('POST /api/v1/notifications', function () {
    it('creates a notification and returns 201', function () {
        $response = $this->postJson('/api/v1/notifications', [
            'user_id'  => 15,
            'type'     => 'order_paid',
            'channels' => ['email', 'sms'],
            'priority' => 'high',
            'payload'  => ['order_id' => 1001],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id', 'uuid', 'user_id', 'type',
                    'channels', 'payload', 'priority', 'status',
                    'created_at', 'updated_at',
                ],
            ])
            ->assertJsonPath('data.user_id', 15)
            ->assertJsonPath('data.type', 'order_paid')
            ->assertJsonPath('data.status', 'pending');
    });

    it('returns 422 when required fields are missing', function () {
        $response = $this->postJson('/api/v1/notifications', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'type', 'channels', 'payload']);
    });

    it('returns 422 when type is not snake_case', function () {
        $response = $this->postJson('/api/v1/notifications', [
            'user_id'  => 1,
            'type'     => 'OrderPaid',
            'channels' => ['email'],
            'payload'  => [],
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    });

    it('returns 422 when unsupported channel is provided', function () {
        $response = $this->postJson('/api/v1/notifications', [
            'user_id'  => 1,
            'type'     => 'test_event',
            'channels' => ['fax'],
            'payload'  => [],
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['channels.0']);
    });
});

describe('GET /api/v1/notifications/{uuid}', function () {
    it('returns 404 for unknown UUID', function () {
        $response = $this->getJson('/api/v1/notifications/non-existent-uuid');
        $response->assertStatus(404);
    });
});

describe('GET /api/v1/health', function () {
    it('returns healthy status', function () {
        $response = $this->getJson('/api/v1/health');
        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['status', 'version', 'checks' => ['database', 'redis']]);
    });
});
