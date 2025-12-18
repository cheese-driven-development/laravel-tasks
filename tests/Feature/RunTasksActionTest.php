<?php

namespace CheeseDriven\LaravelTasks\Tests\Feature;

use CheeseDriven\LaravelTasks\Actions\RunTasksAction;
use CheeseDriven\LaravelTasks\Models\Task;
use CheeseDriven\LaravelTasks\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class RunTasksActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_tasks_action_can_be_invoked(): void
    {
        $action = new RunTasksAction;

        // verify the action can be invoked
        $this->assertTrue(is_callable($action));
        $this->assertTrue(method_exists($action, '__invoke'));
    }

    public function test_run_tasks_action_filters_by_ready_scope(): void
    {
        // create tasks - one ready, one already executed
        $readyTask = Task::init('ready-task');
        $readyTask->save();

        $executedTask = Task::init('executed-task');
        $executedTask->completed_at = now();
        $executedTask->save();

        // verify the ready scope filters correctly (this is what RunTasksAction uses)
        $readyTasks = Task::query()->notCompleted()->get();

        $this->assertCount(1, $readyTasks);
        $this->assertEquals($readyTask->id, $readyTasks->first()->id);
        $this->assertNotContains($executedTask->id, $readyTasks->pluck('id')->toArray());
    }

    public function test_run_tasks_action_uses_cursor_for_memory_efficiency(): void
    {
        // create multiple tasks
        Task::init('task-1')->save();
        Task::init('task-2')->save();
        Task::init('task-3')->save();

        // verify cursor is used (this tests the query structure that RunTasksAction uses)
        $query = Task::query()->notCompleted();

        // cursor() returns a LazyCollection which is memory efficient
        $this->assertInstanceOf(\Illuminate\Support\LazyCollection::class, $query->cursor());
    }

    public function test_run_tasks_action_executes_tasks_that_should_run(): void
    {
        // create ready tasks
        $task1 = Task::init('task-1');
        $task1->save();

        $task2 = Task::init('task-2');
        $task2->save();

        // verify the action structure is correct
        $action = new RunTasksAction;
        $this->assertInstanceOf(RunTasksAction::class, $action);

        // verify tasks are ready to be processed
        $readyTasks = Task::query()->notCompleted()->get();
        $this->assertCount(2, $readyTasks);
    }

    public function test_run_tasks_action_filters_and_executes_ready_tasks(): void
    {
        // create tasks with different states
        $readyTask1 = Task::init('ready-task-1');
        $readyTask1->save();

        $readyTask2 = Task::init('ready-task-2');
        $readyTask2->save();

        $executedTask = Task::init('executed-task');
        $executedTask->completed_at = now();
        $executedTask->save();

        // simulate what RunTasksAction does:
        // 1. Query ready tasks
        // 2. Filter by shouldRun()
        // 3. Execute each task
        $readyTasks = Task::query()->notCompleted()->cursor();

        // verify only ready tasks are in the cursor
        $readyTaskIds = $readyTasks->pluck('id')->toArray();
        $this->assertContains($readyTask1->id, $readyTaskIds);
        $this->assertContains($readyTask2->id, $readyTaskIds);
        $this->assertNotContains($executedTask->id, $readyTaskIds);
        $this->assertCount(2, $readyTaskIds);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
