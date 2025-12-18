<?php

namespace CheeseDriven\LaravelTasks\Actions;

use CheeseDriven\LaravelTasks\Contracts\Action;
use CheeseDriven\LaravelTasks\Models\Task;
use Illuminate\Support\Facades\Log;

/**
 * This action is used for testing.
 */
class LogSomethingAction implements Action
{
    public function __construct(public string $message) {}

    public function handle(Task $task)
    {
        Log::info($this->message);

        $task->complete();
    }
}
