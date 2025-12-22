<?php

namespace CheeseDriven\LaravelTasks\Support;

use CheeseDriven\LaravelTasks\Models\Task;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasTasks
{
    public function tasks(): MorphMany
    {
        return $this->morphMany(config('tasks.models.task', Task::class), 'targetable');
    }
}
