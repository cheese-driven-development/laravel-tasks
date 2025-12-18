<?php

namespace CheeseDriven\LaravelTasks\Contracts;

use CheeseDriven\LaravelTasks\Models\Task;

interface Action
{
    public function handle(Task $task);
}
