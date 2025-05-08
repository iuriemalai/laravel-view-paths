<?php

namespace IurieMalai\ViewPaths\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use IurieMalai\LaravelViewPaths\LaravelViewPathsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            ViewPathsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {

    }
}
