<?php

namespace CheeseDriven\LaravelTasks\Actions;

use CheeseDriven\LaravelTasks\Models\Task;
use CheeseDriven\LaravelTasks\Support\ClassResolver;

class RunTasksAction
{
    use ClassResolver;

    public function __invoke(): void
    {
        static::taskModel()::query()
            ->notCompleted()
            ->with('logs')
            ->cursor()
            ->filter(fn (Task $task) => $task->shouldRun())
            ->each(fn (Task $task) => $task->execute());
    }
}
