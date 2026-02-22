<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->foreignId('type_id')
                ->constrained('notification_types')
                ->restrictOnDelete();
            $table->json('payload');
            $table->json('channels')->comment('Array of channel names, e.g. ["email","sms"]');
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal')->index();
            $table->foreignId('status_id')
                ->constrained('notification_statuses')
                ->restrictOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status_id', 'created_at']);
            $table->index(['priority', 'status_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
