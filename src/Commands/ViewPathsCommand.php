<?php

namespace IurieMalai\ViewPaths\Commands;

use Illuminate\Console\Command;

class ViewPathsCommand extends Command
{
    public $signature = 'laravel-view-paths';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
