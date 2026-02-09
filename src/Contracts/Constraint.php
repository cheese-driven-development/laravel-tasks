<?php

namespace CheeseDriven\LaravelTasks\Contracts;

use CheeseDriven\LaravelTasks\Models\Task;

interface Constraint
{
    /**
     * Whether the task should run.
     */
    public function shouldRun(Task $task): bool;

    /**
     * Whether the task should be marked as completed when this constraint fails.
     * If true, the task will be completed instead of skipped when shouldRun() returns false.
     */
    public function completeOnSkipped(): bool;
}
