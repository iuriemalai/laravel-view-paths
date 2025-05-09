<?php

namespace Tests;

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

    public function getEnvironmentSetUp($app) {}
}
