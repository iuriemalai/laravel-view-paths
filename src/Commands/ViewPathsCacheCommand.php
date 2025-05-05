<?php

namespace IurieMalai\ViewPaths\Commands;

use Illuminate\Console\Command;
use IurieMalai\ViewPaths\Services\ViewPathsService;

class ViewPathsCacheCommand extends Command
{
    public $signature = 'view-paths:cache';

    public $description = 'Cache view paths for improved performance';

    public function handle(ViewPathsService $viewPathsService): int
    {
        $this->info('Warming view paths cache...');

        $viewPathsService->warmCache();

        $cacheInfo = $viewPathsService->getCacheInfo();

        $this->info('View paths cache has been warmed successfully.');
        $this->table(
            ['Cache Enabled', 'Cache Duration', 'Cache Key', 'Is Cached'],
            [[
                $cacheInfo['config']['enabled'] ? 'Yes' : 'No',
                $cacheInfo['config']['duration'],
                $cacheInfo['config']['key'],
                $cacheInfo['config']['is_cached'] ? 'Yes' : 'No',
            ]]
        );

        $regularPaths = $cacheInfo['paths']['paths'] ?? [];
        $namespacedPaths = $cacheInfo['paths']['namespaced_paths'] ?? [];

        if (! empty($regularPaths)) {
            $this->info('Regular view paths:');
            $this->table(['Path'], array_map(fn ($path) => [$path], $regularPaths));
        }

        if (! empty($namespacedPaths)) {
            $this->info('Namespaced view paths:');
            $namespacedData = [];
            foreach ($namespacedPaths as $namespace => $path) {
                $namespacedData[] = [$namespace, $path];
            }
            $this->table(['Namespace', 'Path'], $namespacedData);
        }

        return self::SUCCESS;
    }
}
