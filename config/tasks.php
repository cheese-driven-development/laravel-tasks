<?php

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
];
