<?php

namespace CheeseDriven\LaravelTasks\Commands;

use CheeseDriven\LaravelTasks\Enums\TaskLogStatus;
use CheeseDriven\LaravelTasks\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ListTasksCommand extends Command
{
    protected $signature = 'tasks:list';

    protected $description = 'List the tasks.';

    public function handle(): int
    {
        $tasks = Task::all();

        $this->table(['ID', 'Name', 'Type', 'Created At', 'Scheduled At', 'Status', 'Runs'], $tasks->map(fn (Task $task) => [
            $task->id,
            Str::limit($task->name, 30),
            $task->type,
            $task->created_at->format('Y-m-d H:i:s'),
            $task->scheduled_at?->format('Y-m-d H:i:s'),
            $task->latest_status ?? (isset($task->scheduled_at) && $task->scheduled_at->isFuture() ? 'scheduled' : 'ready to run'),
            $task->logs()->where('status', TaskLogStatus::Pending->value)->count(),
        ]));

        $this->comment('Current time: '.now()->format('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }
}
