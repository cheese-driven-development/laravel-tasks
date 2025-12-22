<?php

namespace CheeseDriven\LaravelTasks\Tests\Feature;

use CheeseDriven\LaravelTasks\Actions\LogSomethingAction;
use CheeseDriven\LaravelTasks\Constraints\OnceConstraint;
use CheeseDriven\LaravelTasks\Enums\TaskType;
use CheeseDriven\LaravelTasks\Models\Task;
use CheeseDriven\LaravelTasks\TaskManager;
use CheeseDriven\LaravelTasks\TestMail;
use CheeseDriven\LaravelTasks\Tests\TestCase;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_can_be_initialized(): void
    {
        $task = Task::init('test-task');

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('test-task', $task->name);
        $this->assertNull($task->id);
    }

    public function test_task_can_be_saved(): void
    {
        $taskManager = new TaskManager;
        $task = $taskManager->init('test-task');
        $task->type(TaskType::Custom);
        $task->action(new LogSomethingAction('Test'));
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
        $task->mailable(new TestMail);
        $task->recipients('test@example.com');
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
        $task = $taskManager->init('test-task');
        $task->type(TaskType::Custom);
        $task->action(new LogSomethingAction('Test'));

        // simulate destructor behavior
        unset($taskManager);

        $this->assertDatabaseHas('tasks', [
            'name' => 'test-task',
        ]);
    }

    public function test_task_can_have_constraints(): void
    {
        $task = Task::init('test-task');
        $task->type(TaskType::Custom);
        $task->action(new LogSomethingAction('Test'));
        $task->constraint(new OnceConstraint);
        $task->save();

        $this->assertTrue($task->passesDefaultConstraints());
    }

    public function test_task_can_be_retrieved_from_database(): void
    {
        $task = Task::init('test-task');
        $task->type(TaskType::Custom);
        $task->action(new LogSomethingAction('Test'));
        $task->save();

        $retrievedTask = Task::find($task->id);

        $this->assertInstanceOf(Task::class, $retrievedTask);
        $this->assertEquals('test-task', $retrievedTask->name);
    }

    public function test_ready_scope_filters_unexecuted_tasks(): void
    {
        $task1 = Task::init('task-1');
        $task1->type(TaskType::Custom);
        $task1->action(new LogSomethingAction('Test'));
        $task1->save();

        $task2 = Task::init('task-2');
        $task2->type(TaskType::Custom);
        $task2->action(new LogSomethingAction('Test'));
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

    public function test_save_throws_exception_when_task_has_no_type(): void
    {
        $task = Task::init('test-task');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Type must be set for tasks');

        $task->save();
    }

    public function test_save_throws_exception_when_mail_task_has_no_mailable(): void
    {
        $task = Task::init('test-task');
        $task->type(TaskType::Mail);
        $task->recipients('test@example.com');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Mailable must be set for mail tasks');

        $task->save();
    }

    public function test_save_throws_exception_when_mail_task_has_no_recipients(): void
    {
        $task = Task::init('test-task');
        $task->type(TaskType::Mail);
        $task->mailable(new TestMail);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Recipients must be set for mail tasks');

        $task->save();
    }

    public function test_save_throws_exception_when_mail_task_has_empty_recipients(): void
    {
        $task = Task::init('test-task');
        $task->type(TaskType::Mail);
        $task->mailable(new TestMail);
        $task->recipients([]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Recipients must be set for mail tasks');

        $task->save();
    }

    public function test_save_throws_exception_when_custom_task_has_no_action(): void
    {
        $task = Task::init('test-task');
        $task->type(TaskType::Custom);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Action must be set for custom tasks');

        $task->save();
    }

    public function test_save_succeeds_when_mail_task_has_all_required_fields(): void
    {
        $task = Task::init('test-task');
        $task->type(TaskType::Mail);
        $task->mailable(new TestMail);
        $task->recipients('test@example.com');
        $task->save();

        $this->assertDatabaseHas('tasks', [
            'name' => 'test-task',
            'type' => TaskType::Mail->value,
        ]);
    }

    public function test_save_succeeds_when_custom_task_has_all_required_fields(): void
    {
        $task = Task::init('test-task');
        $task->type(TaskType::Custom);
        $task->action(new LogSomethingAction('Test message'));

        $task->save();

        $this->assertDatabaseHas('tasks', [
            'name' => 'test-task',
            'type' => TaskType::Custom->value,
        ]);
    }
}
