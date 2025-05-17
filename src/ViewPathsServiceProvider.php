<?php

namespace IurieMalai\ViewPaths;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use IurieMalai\ViewPaths\Commands\ViewPathsCommand;

class ViewPathsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-view-paths')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_view_paths_table')
            ->hasCommand(ViewPathsCommand::class);
    }
}
