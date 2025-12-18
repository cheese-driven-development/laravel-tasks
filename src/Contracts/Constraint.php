<?php

namespace CheeseDriven\LaravelTasks\Contracts;

use CheeseDriven\LaravelTasks\Models\Task;

interface Constraint
{
    public function shouldRun(Task $task): bool;
}
