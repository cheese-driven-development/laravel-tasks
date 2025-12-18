<?php

namespace CheeseDriven\LaravelTasks\Commands;

use CheeseDriven\LaravelTasks\Actions\LogSomethingAction;
use CheeseDriven\LaravelTasks\Actions\ThrowExceptionAction;
use CheeseDriven\LaravelTasks\Enums\TaskType;
use CheeseDriven\LaravelTasks\Task;
use CheeseDriven\LaravelTasks\TestMail;
use Illuminate\Console\Command;

class SeedSampleTasksCommand extends Command
{
    protected $signature = 'tasks:seed';

    protected $description = 'Seed some sample tasks.';

    public function handle(): int
    {
        Task::init('Test Mail Task (will execute in next run)')
            ->type(TaskType::Mail)
            ->mailable(new TestMail)
            ->recipients('test@example.com')
            ->scheduleAt(now()->subMinute())
            ->unique()
            ->save();

        Task::init('Test Mail Task (will execute in 5 minutes)')
            ->type(TaskType::Mail)
            ->mailable(new TestMail)
            ->recipients(['test@example.com'])
            ->scheduleAt(now()->addMinutes(5))
            ->unique()
            ->save();

        Task::init('Test Exception Task (executed directly, with failure, will succeed on the second run)')
            ->type(TaskType::Custom)
            ->action(new ThrowExceptionAction)
            ->scheduleAt(now()->addMonths(2))
            ->unique()
            ->save();

        Task::init('Test Log Task (executed directly, not unique)')
            ->type(TaskType::Custom)
            ->action(new LogSomethingAction('Task Test Log Message'))
            ->save();

        return Command::SUCCESS;
    }
}
