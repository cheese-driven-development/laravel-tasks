<?php

namespace CheeseDriven\LaravelTasks\Constraints;

use CheeseDriven\LaravelTasks\Contracts\Constraint;
use CheeseDriven\LaravelTasks\Models\Task;

class ScheduledConstraint implements Constraint
{
    public function shouldRun(Task $task): bool
    {
        if (is_null($task->scheduled_at)) {
            return true;
        }

        return $task->scheduled_at->isPast();
    }
}
