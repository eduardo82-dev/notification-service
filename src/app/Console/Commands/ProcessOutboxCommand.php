<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Infrastructure\Outbox\OutboxProcessor;
use Illuminate\Console\Command;

final class ProcessOutboxCommand extends Command
{
    protected $signature   = 'outbox:process {--once : Process one batch and exit}';
    protected $description = 'Process unprocessed OUTBOX messages and publish them to the queue';

    public function handle(OutboxProcessor $processor): int
    {
        $this->info('Starting OUTBOX processor...');

        if ($this->option('once')) {
            $count = $processor->process();
            $this->info("Processed {$count} outbox message(s).");
            return self::SUCCESS;
        }

        // Continuous mode (used by scheduler)
        while (true) {
            $count = $processor->process();

            if ($count > 0) {
                $this->info("Processed {$count} outbox message(s).");
            }

            sleep(5);
        }
    }
}
