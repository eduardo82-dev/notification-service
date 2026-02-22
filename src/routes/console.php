<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Process OUTBOX every minute
Schedule::command('outbox:process --once')->everyMinute()->withoutOverlapping();
