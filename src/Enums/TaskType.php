<?php

namespace CheeseDriven\LaravelTasks\Enums;

enum TaskType: string
{
    case Mail = 'mailable';
    case Custom = 'custom';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
