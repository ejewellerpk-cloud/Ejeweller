<?php

namespace App\Console\Commands;

use App\Services\CartRecoveryService;
use Illuminate\Console\Command;

class ProcessCartRecoveryCommand extends Command
{
    protected $signature = 'campaigns:process-recovery {--enqueue : Also enqueue new abandons} {--hours=72}';

    protected $description = 'Enqueue abandoned carts and send due cart-recovery emails';

    public function handle(CartRecoveryService $recovery): int
    {
        if ($this->option('enqueue')) {
            $result = $recovery->enqueueFromAbandons(null, (int) $this->option('hours'));
            $this->info($result['message'] ?? 'Enqueue done');
        }

        $processed = $recovery->processDue();
        $this->info("Sent {$processed['sent']}, failed {$processed['failed']}");

        return self::SUCCESS;
    }
}
