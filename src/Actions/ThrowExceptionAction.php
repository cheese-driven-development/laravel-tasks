<?php

namespace CheeseDriven\LaravelTasks\Actions;

use CheeseDriven\LaravelTasks\Contracts\Action;
use CheeseDriven\LaravelTasks\Enums\TaskLogStatus;
use CheeseDriven\LaravelTasks\Models\Task;
use Exception;

/**
 * This action is used to test the task execution.
 * This action will throw an exception on the first run.
 * It will complete the task successfully after the second run.
 */
class ThrowExceptionAction implements Action
{
    public function handle(Task $task)
    {
        if ($task->logs()->where('status', TaskLogStatus::Failed->value)->exists()) {
            $task->complete();

            return;
        }

        throw new Exception('Test exception');
    }
}
