<?php

namespace IurieMalai\ViewPaths\Services;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Statamic\Facades\Cascade;

/**
 * Manages view paths and their caching for Laravel applications.
 */
class ViewPathsService
{
    protected array $paths;

    protected array $namespacedPaths;

    protected bool $cacheEnabled;

    protected int|string|null $cacheDuration;

    protected string $cacheKey;

    protected bool $loggingEnabled;

    protected string $logLevel;

    protected ?string $logChannel;

    protected bool $logViewsInfo;

    protected Repository $cache;

    protected LogManager $logger;

    /**
     * Initialize the ViewPathsService with configuration values and injected services.
     */
    public function __construct(Repository $cache, LogManager $logger)
    {
        $config = config('view_paths');

        $this->paths = $config['paths'] ?? [];
        $this->namespacedPaths = $config['namespaced_paths'] ?? [];
        $this->cacheEnabled = $config['cache_enabled'] ?? true;
        $this->cacheDuration = $config['cache_duration'] ?? 'forever';
        $this->cacheKey = $config['cache_key'] ?? 'view_paths';

        $this->loggingEnabled = $config['logging']['enabled'] ?? true;
        $this->logLevel = $config['logging']['level'] ?? 'info';
        $this->logChannel = $config['logging']['channel'] ?? null;

        $this->logViewsInfo = $config['log_views_info'] ?? false;

        $this->cache = $cache;
        $this->logger = $logger;

        if ($this->logViewsInfo) {
            $this->viewsInfoLogger();
        }
    }

    /**
     * Logs a message with the configured level and channel.
     */
    protected function log(string $message, ?string $level = null): void
    {
        if (! $this->loggingEnabled) {
            return;
        }

        $logLevel = $level ?? $this->logLevel;
        $logger = $this->logChannel ? $this->logger->channel($this->logChannel) : $this->logger;
        $logger->{$logLevel}($message);
    }

    /**
     * Loads view paths into Laravel's view finder.
     */
    public function loadViewPaths(): void
    {
        try {
            $this->warmCache(); // Always ensure cache is ready first

            // Load regular view paths
            $paths = $this->getPathsFromCache();

            if (empty($paths)) {
                $paths = $this->getValidPaths();
            }

            if (! empty($paths)) {
                if (! $this->cache->has($this->cacheKey)) {
                    $this->log('Retrieved view paths directly', 'info');
                }

                foreach ($paths as $path) {
                    View::prependLocation($path);
                }
                $this->log('Added '.count($paths).' view paths: '.implode(', ', $paths), 'info');
            } else {
                $this->log('No regular view paths configured', 'info');
            }

            // Load namespaced view paths
            $namespacedPaths = $this->getNamespacedPathsFromCache();

            if (empty($namespacedPaths)) {
                $namespacedPaths = $this->getValidNamespacedPaths();
            }

            if (! empty($namespacedPaths)) {
                if (! $this->cache->has($this->cacheKey)) {
                    $this->log('Retrieved namespaced view paths directly', 'info');
                }

                foreach ($namespacedPaths as $namespace => $path) {

                    if ($namespace === 'flux') {
                        // $this->log(hash('xxh128', $namespace));
                        Blade::anonymousComponentPath($path, 'flux');

                        config(['livewire.view_path' => $path . '/../livewire']);
                    }

                    if ($namespace === 'volt-livewire' && class_exists('\Livewire\Volt\Volt')) {
                        \Livewire\Volt\Volt::mount($path);
                        $this->log("Mounted Volt components from path: {$path}", 'info');
                    } else {
                        View::prependNamespace($namespace, $path);
                        $this->log("Added view namespace '{$namespace}' with path: {$path}", 'info');
                    }
                }
            } else {
                $this->log('No namespaced view paths configured', 'info');
            }
        } catch (\Exception $e) {
            $this->log('Failed to load view paths: '.$e->getMessage(), 'error');
        }
    }

    /**
     * Gets cached regular view paths.
     */
    protected function getPathsFromCache(): array
    {
        $data = (array) $this->cache->get($this->cacheKey, ['paths' => [], 'namespaced_paths' => []]);

        if (! empty($data['paths'])) {
            $this->log('Retrieved view paths from cache', 'info');
        }

        return $data['paths'] ?? [];
    }

    /**
     * Gets cached namespaced view paths.
     */
    protected function getNamespacedPathsFromCache(): array
    {
        $data = (array) $this->cache->get($this->cacheKey, ['paths' => [], 'namespaced_paths' => []]);

        if (! empty($data['namespaced_paths'])) {
            $this->log('Retrieved namespaced view paths from cache', 'info');
        }

        return $data['namespaced_paths'] ?? [];
    }

    /**
     * Filters configured paths to only include valid directories.
     */
    protected function getValidPaths(): array
    {
        $result = collect($this->paths)
            ->filter(fn ($path) => is_dir($path))
            ->values()
            ->all();

        $invalidCount = count($this->paths) - count($result);
        if ($invalidCount > 0) {
            $this->log("{$invalidCount} view path(s) were invalid", 'warning');
        }

        return $result;
    }

    /**
     * Filters configured namespaced paths to only include valid directories.
     */
    protected function getValidNamespacedPaths(): array
    {
        $result = [];
        foreach ($this->namespacedPaths as $namespace => $path) {
            if (is_dir($path)) {
                $result[$namespace] = $path;
            } else {
                $this->log("Invalid namespaced path for '{$namespace}': {$path}", 'warning');
            }
        }

        $invalidCount = count($this->namespacedPaths) - count($result);
        if ($invalidCount > 0) {
            $this->log("{$invalidCount} namespaced path(s) were invalid", 'warning');
        }

        return $result;
    }

    /**
     * Warms the view paths cache.
     */
    public function warmCache(): void
    {
        if (! $this->cacheEnabled) {
            $this->clearCache();
            $this->log('Cache warming skipped (caching disabled)', 'info');

            return;
        }

        if ($this->cache->has($this->cacheKey)) {
            return;
        }

        $this->log('Paths not found in cache, warming cache', 'info');

        try {
            $cacheData = [
                'paths' => $this->getValidPaths(),
                'namespaced_paths' => $this->getValidNamespacedPaths(),
            ];

            if ($this->cacheDuration === 'forever') {
                $this->cache->forever($this->cacheKey, $cacheData);
                $this->log('View paths cached forever', 'info');
            } else {
                $duration = $this->parseDuration($this->cacheDuration);
                if ($duration) {
                    $this->cache->put($this->cacheKey, $cacheData, $duration);
                    $this->log('View paths cached with expiration: '.$duration->format('Y-m-d H:i:s'), 'info');
                } else {
                    $this->cache->forever($this->cacheKey, $cacheData);
                    $this->log('View paths cached forever (fallback)', 'info');
                }
            }

            $this->log('Cache warmed with '.count($cacheData['paths']).' regular paths and '.count($cacheData['namespaced_paths']).' namespaced paths', 'info');
        } catch (\Exception $e) {
            $this->log('Failed to warm cache: '.$e->getMessage(), 'error');
        }
    }

    /**
     * Clears the view paths cache.
     */
    public function clearCache(): bool
    {
        if (! $this->cacheEnabled && ! $this->cache->has($this->cacheKey)) {
            $this->log('Cache clearing skipped (caching disabled)', 'info');

            return false;
        }

        try {
            $this->cache->forget($this->cacheKey);
            $this->log("View paths cache cleared (cache key '{$this->cacheKey}')", 'info');

            return true;
        } catch (\Exception $e) {
            $this->log('Failed to clear cache: '.$e->getMessage(), 'error');

            return false;
        }
    }

    /**
     * Parses a duration string or integer into a DateTimeInterface.
     */
    protected function parseDuration(int|string|null $duration): ?\DateTimeInterface
    {
        if ($duration === 'forever' || $duration === null || empty($duration)) {
            return null;
        }

        if (is_int($duration)) {
            return now()->addSeconds($duration);
        }

        if (preg_match('/^(\d+)([smhdwMy])$/', $duration, $matches)) {
            [, $amount, $unit] = $matches;
            $amount = (int) $amount;

            return match ($unit) {
                's' => now()->addSeconds($amount),
                'm' => now()->addMinutes($amount),
                'h' => now()->addHours($amount),
                'd' => now()->addDays($amount),
                'w' => now()->addWeeks($amount),
                'M' => now()->addMonths($amount),
                'y' => now()->addYears($amount), // @phpstan-ignore match.alwaysTrue
                default => now()->addHour(),
            };
        }

        $this->log("Unrecognized duration format: {$duration}, defaulting to 1 hour", 'warning');

        return now()->addHour();
    }

    /**
     * Get current cache information.
     */
    public function getCacheInfo(): array
    {
        return [
            'config' => [
                'enabled' => $this->cacheEnabled,
                'duration' => $this->cacheDuration,
                'key' => $this->cacheKey,
                'is_cached' => $this->isCached(),
            ],
            'paths' => $this->getCachedViewPaths(),
        ];
    }

    /**
     * Get cached view paths.
     */
    public function getCachedViewPaths(): array
    {
        if (! $this->cacheEnabled || ! $this->cache->has($this->cacheKey)) {
            return ['paths' => [], 'namespaced_paths' => []];
        }

        return (array) $this->cache->get($this->cacheKey, ['paths' => [], 'namespaced_paths' => []]);
    }

    /**
     * Check if view paths are currently cached.
     */
    public function isCached(): bool
    {
        return $this->cacheEnabled && $this->cache->has($this->cacheKey);
    }

    /**
     * Get configured namespaced paths.
     */
    public function getNamespacedPaths(): array
    {
        return $this->namespacedPaths;
    }

    /**
     * Log view names and paths for the current page.
     */
    public function viewsInfoLogger(): void
    {
        if (! $this->logViewsInfo) {
            return;
        }

        View::composer('*', function ($view) {
            $viewName = $view->getName();
            $viewPath = $view->getPath();
            $this->log("$viewName - $viewPath", 'info');
        });
    }

    /**
     * Set the application locale based on the session.
     * If using Statamic, also set the locale in the Cascade.
     */
    public function setLocale(): void
    {
        if (class_exists('Statamic\Facades\Cascade')) {
            try {
                \Statamic\Facades\Cascade::hydrated(function ($cascade) {
                    $locale = Session::get('locale', config('app.locale'));

                    if (App::currentLocale() !== $locale) {
                        $cascade->set('current_locale', $locale);
                        App::setLocale($locale);
                    }
                });
            } catch (\Exception $e) {
                $this->log("Failed to set Statamic Cascade locale: {$e->getMessage()}", 'warning');
            }
        }
    }
}
