<?php

namespace CheeseDriven\LaravelTasks\Models;

use CheeseDriven\LaravelTasks\Enums\TaskLogStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class TaskLog extends Model
{
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
