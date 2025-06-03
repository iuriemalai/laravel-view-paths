<?php

namespace IurieMalai\ViewPaths;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Log\LogManager;
use IurieMalai\ViewPaths\Commands\ViewPathsCacheCommand;
use IurieMalai\ViewPaths\Commands\ViewPathsClearCommand;
use IurieMalai\ViewPaths\Commands\ViewPathsListCommand;
use IurieMalai\ViewPaths\Services\ViewPathsService;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\Commands\Concerns;

class ViewPathsServiceProvider extends PackageServiceProvider
{
    /**
     * Configure the package.
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-view-paths')
            ->hasConfigFile('view_paths')
            ->hasCommands([
                ViewPathsCacheCommand::class,
                ViewPathsClearCommand::class,
                ViewPathsListCommand::class,
            ])
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile();
            });
    }

    /**
     * Bootstrap the package services.
     */
    //    public function boot(): void
    //    {
    //        parent::boot();

    // Publish config file
    //        if ($this->app->runningInConsole()) {
    //            $this->publishes([
    //                __DIR__.'/../config/view_paths.php' => config_path('view_paths.php'),
    //            ], 'laravel-view-paths-config');
    //        }
    //    }

    /**
     * Register the ViewPathsService service.
     */
    public function packageRegistered(): void
    {
        $this->app->singleton(ViewPathsService::class, function (Application $app) {
            return new ViewPathsService(
                $app->make(Repository::class),
                $app->make(LogManager::class)
            );
        });
    }

    /**
     * Bootstrap the ViewPathsService service.
     */
    public function packageBooted(): void
    {
        if (! config('view_paths.enabled')) {
            return;
        }

        // Only load paths in web context or when running queue workers
        if (! $this->app->runningInConsole() || $this->isRunningInQueue()) {
            $viewPathsService = $this->app->make(ViewPathsService::class);
            $viewPathsService->loadViewPaths();
            $viewPathsService->setLocale();
        }
    }

    /**
     * Determine if the application is running in the queue worker.
     */
    protected function isRunningInQueue(): bool
    {
        return $this->app->bound('queue.worker') ||
            isset($_SERVER['argv'][0]) && str_contains($_SERVER['argv'][0], 'queue:work');
    }
}
