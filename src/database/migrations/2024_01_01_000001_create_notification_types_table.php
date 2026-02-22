<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique()->comment('Snake-case event type, e.g. order_paid');
            $table->string('description')->default('');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_types');
    }
};
