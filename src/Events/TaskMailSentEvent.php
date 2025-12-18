<?php

namespace CheeseDriven\LaravelTasks\Events;

use CheeseDriven\LaravelTasks\Models\Task;

class TaskMailSentEvent
{
    public Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }
}
