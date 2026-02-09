<?php

namespace CheeseDriven\LaravelTasks\Actions;

use CheeseDriven\LaravelTasks\Contracts\Action;
use CheeseDriven\LaravelTasks\Enums\TaskLogStatus;
use CheeseDriven\LaravelTasks\Models\Task;

class TaskCompletedAction implements Action
{
    public function handle(Task $task)
    {
        if ($this->shouldMarkCompleted($task->fresh())) {
            $task->completed_at = now();
            $task->save();
        }
    }

    protected function shouldMarkCompleted(Task $task): bool
    {
        if (isset($task->completed_at)) {
            return false;
        }

        return $task->logs()->where('status', TaskLogStatus::Success->value)->exists();
    }
}
