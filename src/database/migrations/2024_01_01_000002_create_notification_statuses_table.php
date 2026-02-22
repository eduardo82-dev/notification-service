<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique()->comment('pending|processing|sent|failed|partial');
            $table->string('description')->default('');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_statuses');
    }
};
