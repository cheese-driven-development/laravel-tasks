<?php

namespace CheeseDriven\LaravelTasks;

use CheeseDriven\LaravelTasks\Actions\RunTasksAction;
use CheeseDriven\LaravelTasks\Models\Task;
use CheeseDriven\LaravelTasks\Support\ClassResolver;
use Illuminate\Support\Facades\App;

class TaskManager
{
    use ClassResolver;

    private ?Task $instance;

    public function init(string $name = ''): Task
    {
        return $this->instance = (static::taskModel())::init($name);
    }

    public function run(): void
    {
        App::make(RunTasksAction::class)();
    }

    public function __destruct()
    {
        if (isset($this->instance) && $this->instance && ! $this->instance->wasRecentlyCreated) {
            $this->instance->save();
        }
    }
}
