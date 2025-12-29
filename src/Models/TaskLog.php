<?php

namespace CheeseDriven\LaravelTasks\Models;

use CheeseDriven\LaravelTasks\Enums\TaskLogStatus;
use CheeseDriven\LaravelTasks\Support\ClassResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class TaskLog extends Model
{
    use ClassResolver;

    protected $guarded = [];

    protected $casts = [
        'status' => TaskLogStatus::class,
        'message' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getTable()
    {
        return Config::get('tasks.tables.logs', 'task_logs');
    }

    public function task()
    {
        return $this->belongsTo(static::taskModel());
    }
}
