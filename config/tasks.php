<?php

use CheeseDriven\LaravelTasks\Constraints\OnceConstraint;
use CheeseDriven\LaravelTasks\Constraints\ScheduledConstraint;

return [
    'tables' => [
        'tasks' => 'tasks',
        'logs' => 'task_logs',
    ],
    'models' => [
        'task' => CheeseDriven\LaravelTasks\Models\Task::class,
        'log' => CheeseDriven\LaravelTasks\Models\TaskLog::class,
    ],
    'actions' => [
        'send_mail' => CheeseDriven\LaravelTasks\Actions\SendMailAction::class,
    ],
    'queues' => [
        'send_mail' => 'default',
    ],
    'default_constraints' => [
        OnceConstraint::class,
        ScheduledConstraint::class,
    ],
];
