<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_messages', function (Blueprint $table) {
            $table->id();
            $table->string('aggregate_type', 100)->comment('e.g. Notification');
            $table->string('aggregate_id', 100)->comment('UUID of the aggregate root');
            $table->string('event_type', 100)->comment('e.g. notification.created');
            $table->json('payload');
            $table->boolean('processed')->default(false)->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();

            $table->index(['processed', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
    }
};
