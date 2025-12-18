<?php

namespace CheeseDriven\LaravelTasks\Tests;

use CheeseDriven\LaravelTasks\TaskServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            TaskServiceProvider::class,
        ];
    }
}
