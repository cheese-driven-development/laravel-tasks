<?php

namespace CheeseDriven\LaravelTasks\Tests\Feature;

use CheeseDriven\LaravelTasks\Constraints\OnceConstraint;
use CheeseDriven\LaravelTasks\Enums\TaskType;
use CheeseDriven\LaravelTasks\Models\Task;
use CheeseDriven\LaravelTasks\TaskManager;
use CheeseDriven\LaravelTasks\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_can_be_initialized(): void
    {
        $taskManager = new TaskManager;
        $task = $taskManager->init('test-task');

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('test-task', $task->name);
        $this->assertNull($task->id);
    }

    public function test_task_can_be_saved(): void
    {
        $taskManager = new TaskManager;
        $task = $taskManager->init('test-task');
        $task->save();

        $this->assertDatabaseHas('tasks', [
            'name' => 'test-task',
        ]);

        $this->assertNotNull($task->id);
    }

    public function test_task_can_have_type(): void
    {
        $task = Task::init('test-task');
        $task->type(TaskType::Mail);
        $task->save();

        $this->assertEquals(TaskType::Mail->value, $task->type);
        $this->assertDatabaseHas('tasks', [
            'name' => 'test-task',
            'type' => TaskType::Mail->value,
        ]);
    }

    public function test_task_manager_destructor_saves_task(): void
    {
        $taskManager = new TaskManager;
        $taskManager->init('test-task');

        // simulate destructor behavior
        unset($taskManager);

        $this->assertDatabaseHas('tasks', [
            'name' => 'test-task',
        ]);
    }

    public function test_task_can_have_constraints(): void
    {
        $task = Task::init('test-task');
        $task->constraint(new OnceConstraint);
        $task->save();

        $this->assertTrue($task->passesDefaultConstraints());
    }

    public function test_task_can_be_retrieved_from_database(): void
    {
        $task = Task::init('test-task');
        $task->save();

        $retrievedTask = Task::find($task->id);

        $this->assertInstanceOf(Task::class, $retrievedTask);
        $this->assertEquals('test-task', $retrievedTask->name);
    }

    public function test_ready_scope_filters_unexecuted_tasks(): void
    {
        $task1 = Task::init('task-1');
        $task1->save();

        $task2 = Task::init('task-2');
        $task2->completed_at = now();
        $task2->save();

        $readyTasks = Task::query()->notCompleted()->get();

        $this->assertCount(1, $readyTasks);
        $this->assertNotNull($readyTasks->first());
        $this->assertEquals('task-1', $readyTasks->first()->name);
    }

    public function test_task_manager_run_method_exists(): void
    {
        $taskManager = new TaskManager;

        // verify the run method exists and can be called
        $this->assertTrue(method_exists($taskManager, 'run'));
    }
}
