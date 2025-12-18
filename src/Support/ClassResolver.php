<?php

namespace CheeseDriven\LaravelTasks\Support;

use CheeseDriven\LaravelTasks\Actions\SendMailAction;
use CheeseDriven\LaravelTasks\Models\Task;
use CheeseDriven\LaravelTasks\Models\TaskLog;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

trait ClassResolver
{
    public static function taskModel(): Task
    {
        return App::make(Config::get('tasks.models.task', Task::class));
    }

    public static function taskLogModel(): TaskLog
    {
        return App::make(Config::get('tasks.models.log', TaskLog::class));
    }

    public static function sendMailAction(): SendMailAction
    {
        return App::make(Config::get('tasks.actions.send_mail', SendMailAction::class));
    }
}
