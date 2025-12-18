<?php

namespace CheeseDriven\LaravelTasks\Commands;

use CheeseDriven\LaravelTasks\Models\Task;
use Illuminate\Console\Command;

class PruneTasksCommand extends Command
{
    protected $signature = 'tasks:prune {--days=30 : The number of days to retain tasks and logs}';

    protected $description = 'Prune the tasks and logs.';

    public function handle(): int
    {
        $before = now()->subDays((int) $this->option('days'));

        $totalDeleted = Task::query()
            ->where('created_at', '<', $before)
            ->delete();

        $this->info("Pruned {$totalDeleted} tasks (and their logs).");

        return Command::SUCCESS;
    }
}
