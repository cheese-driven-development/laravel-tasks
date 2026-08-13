<?php

use CheeseDriven\LaravelTasks\Actions\SendMailAction;
use CheeseDriven\LaravelTasks\Models\Task;
use CheeseDriven\LaravelTasks\Models\TaskLog;

return [
    'tables' => [
        'tasks' => 'tasks',
        'logs' => 'task_logs',
    ],
    'models' => [
        'task' => Task::class,
        'log' => TaskLog::class,
    ],
    'actions' => [
        'send_mail' => SendMailAction::class,
    ],
    'queues' => [
        'send_mail' => 'default',
    ],
    // number of failed executions before a task is marked exhausted and stops retrying, set to null to retry forever.
    'max_attempts' => 3,
];
