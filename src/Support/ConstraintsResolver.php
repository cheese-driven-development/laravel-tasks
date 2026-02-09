<?php

namespace CheeseDriven\LaravelTasks\Support;

use CheeseDriven\LaravelTasks\Constraints\OnceConstraint;
use CheeseDriven\LaravelTasks\Constraints\ScheduledConstraint;
use CheeseDriven\LaravelTasks\Contracts\Constraint;
use Illuminate\Support\Facades\Config;

trait ConstraintsResolver
{
    /**
     * Get the default constraints classes from config.
     *
     * @return array<string>
     */
    protected function getDefaultConstraintClasses(): array
    {
        return Config::get('tasks.default_constraints', [
            OnceConstraint::class,
            ScheduledConstraint::class,
        ]);
    }

    /**
     * Default constraints, always applied to all tasks.
     */
    public function passesDefaultConstraints(): bool
    {
        return collect($this->getDefaultConstraintClasses())
            ->map(fn (string $class) => app($class))
            ->every(fn (Constraint $constraint) => $constraint->shouldRun($this));
    }

    /**
     * Check if any failing default constraint wants to complete the task on skip.
     *
     * @return bool True if any failing constraint has completeOnSkipped() returning true
     */
    public function shouldCompleteOnDefaultConstraintFailure(): bool
    {
        $constraints = collect($this->getDefaultConstraintClasses())
            ->map(fn (string $class) => app($class));

        foreach ($constraints as $constraint) {
            if (! $constraint->shouldRun($this) && $constraint->completeOnSkipped()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Custom constraints, only applied when provided.
     */
    public function passesCustomConstraints(): bool
    {
        return collect($this->constraints)
            ->map(fn (string $constraint) => unserialize($constraint))
            ->filter(fn ($constraint) => $constraint instanceof Constraint)
            ->every(fn (Constraint $constraint) => $constraint->shouldRun($this));
    }

    /**
     * Check if any failing custom constraint wants to complete the task on skip.
     *
     * @return bool True if any failing constraint has completeOnSkipped() returning true
     */
    public function shouldCompleteOnCustomConstraintFailure(): bool
    {
        $constraints = collect($this->constraints)
            ->map(fn (string $constraint) => unserialize($constraint))
            ->filter(fn ($constraint) => $constraint instanceof Constraint);

        foreach ($constraints as $constraint) {
            if (! $constraint->shouldRun($this) && $constraint->completeOnSkipped()) {
                return true;
            }
        }

        return false;
    }
}
