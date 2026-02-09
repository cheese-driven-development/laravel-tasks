<?php

namespace CheeseDriven\LaravelTasks\Constraints;

use CheeseDriven\LaravelTasks\Contracts\Constraint;
use CheeseDriven\LaravelTasks\Models\Task;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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

        try {
            $task->targetable->refresh();
        } catch (ModelNotFoundException $e) {
            return false;
        }

        if ($task->targetable->isSoftDeletable()) {
            return ! $task->targetable->trashed();
        }

        return true;
    }

    public function completeOnSkipped(): bool
    {
        return true;
    }
}
