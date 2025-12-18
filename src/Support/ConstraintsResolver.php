<?php

namespace CheeseDriven\LaravelTasks\Support;

use CheeseDriven\LaravelTasks\Constraints\OnceConstraint;
use CheeseDriven\LaravelTasks\Constraints\ScheduledConstraint;
use CheeseDriven\LaravelTasks\Contracts\Constraint;

trait ConstraintsResolver
{
    /**
     * Default constraints, always applied to all tasks.
     */
    public function passesDefaultConstraints(): bool
    {
        return collect([
            OnceConstraint::class,
            ScheduledConstraint::class,
        ])
            ->map(fn (string $class) => app($class))
            ->every(fn (Constraint $constraint) => $constraint->shouldRun($this));
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
}
