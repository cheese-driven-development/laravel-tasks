<?php

namespace CheeseDriven\LaravelTasks\Constraints;

use CheeseDriven\LaravelTasks\Contracts\Constraint;
use CheeseDriven\LaravelTasks\Models\Task;

/**
 * This constraint ensures that a completed task is not run again.
 */
class OnceConstraint implements Constraint
{
    public function shouldRun(Task $task): bool
    {
        return is_null($task->completed_at);
    }
}
