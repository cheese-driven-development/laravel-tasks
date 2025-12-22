<?php

namespace CheeseDriven\LaravelTasks\Constraints;

use CheeseDriven\LaravelTasks\Contracts\Constraint;
use CheeseDriven\LaravelTasks\Models\Task;
use Illuminate\Database\Eloquent\Model;

class ModelNotDeletedConstraint implements Constraint
{
    public function __construct(public Model $model) {}

    public function shouldRun(Task $task): bool
    {
        if (! $this->model->exists()) {
            return false;
        }

        $this->model->refresh();

        if ($this->model->isSoftDeletable()) {
            return ! $this->model->trashed();
        }

        return true;
    }
}
