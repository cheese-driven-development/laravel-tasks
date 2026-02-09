<?php

namespace CheeseDriven\LaravelTasks\Constraints;

use CheeseDriven\LaravelTasks\Contracts\Constraint;
use CheeseDriven\LaravelTasks\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ModelNotDeletedConstraint implements Constraint
{
    public function __construct(public Model $model) {}

    public function shouldRun(Task $task): bool
    {
        if (! $this->model->exists()) {
            return false;
        }

        try {
            $this->model->refresh();
        } catch (ModelNotFoundException $e) {
            return false;
        }

        if ($this->model->isSoftDeletable()) {
            return ! $this->model->trashed();
        }

        return true;
    }
}
