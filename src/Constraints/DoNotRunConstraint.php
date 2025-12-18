<?php

namespace CheeseDriven\LaravelTasks\Constraints;

use CheeseDriven\LaravelTasks\Contracts\Constraint;
use CheeseDriven\LaravelTasks\Models\Task;

/**
 * This constraint is just for testing purposes.
 */
class DoNotRunConstraint implements Constraint
{
    public function shouldRun(Task $task): bool
    {
        return false;
    }
}
