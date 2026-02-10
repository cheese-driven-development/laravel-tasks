<?php

namespace CheeseDriven\LaravelTasks;

use CheeseDriven\LaravelTasks\Commands\ListTasksCommand;
use CheeseDriven\LaravelTasks\Commands\PruneTasksCommand;
use CheeseDriven\LaravelTasks\Commands\RunTasksCommand;
use CheeseDriven\LaravelTasks\Commands\SeedSampleTasksCommand;
use Illuminate\Support\ServiceProvider;

class TaskServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('task-manager', function () {
            return new TaskManager;
        });

        $this->commands([
            RunTasksCommand::class,
            ListTasksCommand::class,
            SeedSampleTasksCommand::class,
            PruneTasksCommand::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/tasks.php' => $this->app->configPath('tasks.php'),
        ], 'laravel-tasks-config');

        if (empty(glob(database_path('migrations').'/*_create_tasks_tables.php'))) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_tasks_tables.stub.php' => database_path('migrations/'.date('Y_m_d_His', now()->subMinute()->timestamp).'_create_tasks_tables.php'),
            ], 'laravel-tasks-migrations');
        }
    }
}
