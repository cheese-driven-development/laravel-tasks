<?php

namespace CheeseDriven\LaravelTasks\Commands;

use CheeseDriven\LaravelTasks\TaskManager;
use Illuminate\Console\Command;

class RunTasksCommand extends Command
{
    protected $signature = 'tasks:run';

    protected $description = 'Run the tasks in Kernel.';

    public function handle(): int
    {
        (new TaskManager)->run();

        return Command::SUCCESS;
    }
}
