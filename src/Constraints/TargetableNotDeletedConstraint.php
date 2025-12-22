<?php

namespace CheeseDriven\LaravelTasks\Constraints;

use CheeseDriven\LaravelTasks\Contracts\Constraint;
use CheeseDriven\LaravelTasks\Models\Task;

class TargetableNotDeletedConstraint implements Constraint
{
    public function shouldRun(Task $task): bool
    {
        if (! $task->targetable) {
            return true;
        }

        if (! $task->targetable->exists()) {
            return false;
        }

        $task->targetable->refresh();

        if ($task->targetable->isSoftDeletable()) {
            return ! $task->targetable->trashed();
        }

        return true;
    }
}
