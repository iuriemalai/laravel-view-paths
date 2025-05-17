<?php

namespace IurieMalai\ViewPaths\Commands;

use Illuminate\Console\Command;
use IurieMalai\ViewPaths\Services\ViewPathsService;

class ViewPathsListCommand extends Command
{
    public $signature = 'view-paths:list';

    public $description = 'List all configured and cached view paths';

    public function handle(ViewPathsService $viewPathsService): int
    {
        $cacheInfo = $viewPathsService->getCacheInfo();

        $this->info('View Paths Configuration:');
        $this->table(
            ['Cache Enabled', 'Cache Duration', 'Cache Key', 'Is Cached'],
            [[
                $cacheInfo['config']['enabled'] ? 'Yes' : 'No',
                $cacheInfo['config']['duration'],
                $cacheInfo['config']['key'],
                $cacheInfo['config']['is_cached'] ? 'Yes' : 'No',
            ]]
        );

        // Get cached paths
        $regularPaths = $cacheInfo['paths']['paths'] ?? [];
        $namespacedPaths = $cacheInfo['paths']['namespaced_paths'] ?? [];

        $this->info('Cached Paths:');

        if (! empty($regularPaths)) {
            $this->info('Regular view paths:');
            $this->table(['Path'], array_map(fn ($path) => [$path], $regularPaths));
        } else {
            $this->info('No regular view paths in cache.');
        }

        if (! empty($namespacedPaths)) {
            $this->info('Namespaced view paths:');
            $namespacedData = [];
            foreach ($namespacedPaths as $namespace => $path) {
                $namespacedData[] = [$namespace, $path];
            }
            $this->table(['Namespace', 'Path'], $namespacedData);
        } else {
            $this->info('No namespaced view paths in cache.');
        }

        // Show configured paths that haven't been cached yet
        if (! $cacheInfo['config']['is_cached']) {
            $this->info('Configured paths (not cached):');

            // Get current configuration
            $config = config('view_paths');
            $configuredPaths = $config['paths'] ?? [];
            $configuredNamespacedPaths = $config['namespaced_paths'] ?? [];

            if (! empty($configuredPaths)) {
                $this->info('Regular view paths:');
                $this->table(['Path'], array_map(fn ($path) => [$path], $configuredPaths));
            } else {
                $this->info('No regular view paths configured.');
            }

            if (! empty($configuredNamespacedPaths)) {
                $this->info('Namespaced view paths:');
                $namespacedData = [];
                foreach ($configuredNamespacedPaths as $namespace => $path) {
                    $namespacedData[] = [$namespace, $path];
                }
                $this->table(['Namespace', 'Path'], $namespacedData);
            } else {
                $this->info('No namespaced view paths configured.');
            }
        }

        return self::SUCCESS;
    }
}
