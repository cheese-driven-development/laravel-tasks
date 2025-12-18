<?php

namespace CheeseDriven\LaravelTasks;

use Illuminate\Support\Facades\Facade;

class Task extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'task-manager';
    }
}
