<?php

namespace App\Console\Commands;

use App\Services\DefaultPagesService;
use Illuminate\Console\Command;

class EnsureDefaultStorePagesCommand extends Command
{
    protected $signature = 'store:ensure-pages';

    protected $description = 'Create default store footer pages (About, Contact, FAQ, policies) if missing';

    public function handle(DefaultPagesService $defaultPagesService): int
    {
        $defaultPagesService->ensure();
        $this->info('Default store pages are ready.');

        return self::SUCCESS;
    }
}
