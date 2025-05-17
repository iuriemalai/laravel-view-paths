<?php

namespace IurieMalai\ViewPaths\Commands;

use Illuminate\Console\Command;
use IurieMalai\ViewPaths\Services\ViewPathsService;

class ViewPathsClearCommand extends Command
{
    public $signature = 'view-paths:clear';

    public $description = 'Clear the view paths cache';

    public function handle(ViewPathsService $viewPathsService): int
    {
        $this->info('Clearing view paths cache...');
        $result = $viewPathsService->clearCache();

        if ($result) {
            $this->info('View paths cache has been cleared successfully.');
        } else {
            $this->info('No view paths cache found or cache is disabled.');
        }

        return self::SUCCESS;
    }
}
