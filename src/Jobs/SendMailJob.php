<?php

namespace CheeseDriven\LaravelTasks\Jobs;

use CheeseDriven\LaravelTasks\Models\Task;
use CheeseDriven\LaravelTasks\Support\ClassResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMailJob implements ShouldBeUnique, ShouldQueue
{
    use ClassResolver;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public bool $deleteWhenMissingModels = true;

    public Task $task;

    /** @var string */
    public $queue;

    public function __construct(Task $task)
    {
        $this->task = $task;

        $this->queue = config('tasks.queues.send_mail', 'default');
    }

    public function uniqueId(): string
    {
        return 'task-'.$this->task->id;
    }

    public function handle(): void
    {
        static::sendMailAction()->handle($this->task);
    }
}
