<?php

namespace CheeseDriven\LaravelTasks\Tests\Feature;

use CheeseDriven\LaravelTasks\Actions\LogSomethingAction;
use CheeseDriven\LaravelTasks\Actions\RunTasksAction;
use CheeseDriven\LaravelTasks\Contracts\Action;
use CheeseDriven\LaravelTasks\Contracts\Constraint;
use CheeseDriven\LaravelTasks\Enums\TaskLogStatus;
use CheeseDriven\LaravelTasks\Enums\TaskType;
use CheeseDriven\LaravelTasks\Models\Task;
use CheeseDriven\LaravelTasks\Tests\TestCase;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

class MaxAttemptsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CountingFailAction::$runs = 0;
    }

    public function test_task_is_marked_failed_permanently_after_max_attempts(): void
    {
        $task = $this->failingTask();

        (new RunTasksAction)();
        (new RunTasksAction)();
        (new RunTasksAction)();

        $task->refresh();

        $this->assertEquals(3, $task->attempts);
        $this->assertEquals(3, CountingFailAction::$runs);
        $this->assertEquals(TaskLogStatus::Exhausted->value, $task->latest_status);
        $this->assertNotNull($task->completed_at);
        $this->assertDatabaseHas('task_logs', [
            'task_id' => $task->id,
            'status' => TaskLogStatus::Exhausted->value,
            'message' => 'Stopped after 3 failed attempts',
        ]);
    }

    public function test_failed_permanently_task_is_not_executed_again(): void
    {
        $task = $this->failingTask();

        (new RunTasksAction)();
        (new RunTasksAction)();
        (new RunTasksAction)();
        (new RunTasksAction)();

        $task->refresh();

        $this->assertEquals(3, CountingFailAction::$runs);
        $this->assertEquals(3, $task->attempts);
        $this->assertNotNull($task->completed_at);
        $this->assertFalse(Task::query()->notCompleted()->whereKey($task->id)->exists());
    }

    public function test_constraint_skips_do_not_count_as_failed_attempts(): void
    {
        $task = Task::init('skipped-task');
        $task->type(TaskType::Custom)
            ->action(new CountingFailAction)
            ->constraint(new NeverRunConstraint)
            ->save();

        (new RunTasksAction)();
        (new RunTasksAction)();

        $task->refresh();

        $this->assertEquals(0, CountingFailAction::$runs);
        $this->assertEquals(0, $task->attempts);
        $this->assertNull($task->completed_at);
        $this->assertEquals(TaskLogStatus::Skipped->value, $task->latest_status);
    }

    public function test_successful_task_is_not_failed_permanently(): void
    {
        $task = Task::init('success-task');
        $task->type(TaskType::Custom)
            ->action(new LogSomethingAction('ok'))
            ->save();

        (new RunTasksAction)();

        $task->refresh();

        $this->assertEquals(0, $task->attempts);
        $this->assertEquals(TaskLogStatus::Success->value, $task->latest_status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_max_attempts_null_retries_forever(): void
    {
        Config::set('tasks.max_attempts', null);

        $task = $this->failingTask();

        (new RunTasksAction)();
        (new RunTasksAction)();
        (new RunTasksAction)();
        (new RunTasksAction)();

        $task->refresh();

        $this->assertEquals(4, CountingFailAction::$runs);
        $this->assertEquals(4, $task->attempts);
        $this->assertNull($task->completed_at);
        $this->assertEquals(TaskLogStatus::Failed->value, $task->latest_status);
    }

    public function test_custom_max_attempts_is_respected(): void
    {
        Config::set('tasks.max_attempts', 1);

        $task = $this->failingTask();

        (new RunTasksAction)();

        $task->refresh();

        $this->assertEquals(1, $task->attempts);
        $this->assertEquals(TaskLogStatus::Exhausted->value, $task->latest_status);
        $this->assertNotNull($task->completed_at);
    }

    protected function failingTask(): Task
    {
        $task = Task::init('failing-task')
            ->type(TaskType::Custom)
            ->action(new CountingFailAction);

        $task->save();

        return $task;
    }
}

class CountingFailAction implements Action
{
    public static int $runs = 0;

    public function handle(Task $task): void
    {
        self::$runs++;

        throw new Exception('Task failed');
    }
}

class NeverRunConstraint implements Constraint
{
    public function shouldRun(Task $task): bool
    {
        return false;
    }

    public function completeOnSkipped(): bool
    {
        return false;
    }
}
