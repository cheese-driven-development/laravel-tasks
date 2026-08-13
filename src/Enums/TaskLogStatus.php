<?php

namespace CheeseDriven\LaravelTasks\Enums;

enum TaskLogStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
    case Exhausted = 'exhausted';
    case Skipped = 'skipped';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
