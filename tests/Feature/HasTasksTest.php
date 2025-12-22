<?php

namespace CheeseDriven\LaravelTasks\Tests\Feature;

use CheeseDriven\LaravelTasks\Actions\LogSomethingAction;
use CheeseDriven\LaravelTasks\Enums\TaskType;
use CheeseDriven\LaravelTasks\Models\Task;
use CheeseDriven\LaravelTasks\Support\HasTasks;
use CheeseDriven\LaravelTasks\Tests\TestCase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class HasTasksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // create the test table if it doesn't exist
        if (! Schema::hasTable('has_tasks_models')) {
            Schema::create('has_tasks_models', function ($table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }
    }

    public function test_model_can_access_tasks_relationship(): void
    {
        $model = new ModelWithTasks;
        $model->name = 'Test Model';
        $model->save();

        // verify the tasks relationship exists
        $this->assertTrue(method_exists($model, 'tasks'));
        $this->assertInstanceOf(MorphMany::class, $model->tasks());
    }

    public function test_model_can_retrieve_associated_tasks(): void
    {
        $model = new ModelWithTasks;
        $model->name = 'Test Model';
        $model->save();

        // create tasks associated with this model
        $task1 = Task::init('Task 1');
        $task1->type(TaskType::Custom);
        $task1->action(new LogSomethingAction('Test'));
        $task1->target($model);
        $task1->save();

        $task2 = Task::init('Task 2');
        $task2->type(TaskType::Custom);
        $task2->action(new LogSomethingAction('Test'));
        $task2->target($model);
        $task2->save();

        // retrieve tasks through the relationship
        $tasks = $model->tasks;

        $this->assertCount(2, $tasks);
        $this->assertTrue($tasks->contains('id', $task1->id));
        $this->assertTrue($tasks->contains('id', $task2->id));
    }

    public function test_model_only_retrieves_its_own_tasks(): void
    {
        $model1 = new ModelWithTasks;
        $model1->name = 'Model 1';
        $model1->save();

        $model2 = new ModelWithTasks;
        $model2->name = 'Model 2';
        $model2->save();

        // create tasks for model1
        $task1 = Task::init('Task for Model 1');
        $task1->type(TaskType::Custom);
        $task1->action(new LogSomethingAction('Test'));
        $task1->target($model1);
        $task1->save();

        $task2 = Task::init('Another Task for Model 1');
        $task2->type(TaskType::Custom);
        $task2->action(new LogSomethingAction('Test'));
        $task2->target($model1);
        $task2->save();

        // create task for model2
        $task3 = Task::init('Task for Model 2');
        $task3->type(TaskType::Custom);
        $task3->action(new LogSomethingAction('Test'));
        $task3->target($model2);
        $task3->save();

        // create task without target
        $task4 = Task::init('Task without target');
        $task4->type(TaskType::Custom);
        $task4->action(new LogSomethingAction('Test'));
        $task4->save();

        // verify model1 only gets its own tasks
        $model1Tasks = $model1->tasks;
        $this->assertCount(2, $model1Tasks);
        $this->assertTrue($model1Tasks->contains('id', $task1->id));
        $this->assertTrue($model1Tasks->contains('id', $task2->id));
        $this->assertFalse($model1Tasks->contains('id', $task3->id));
        $this->assertFalse($model1Tasks->contains('id', $task4->id));

        // verify model2 only gets its own tasks
        $model2Tasks = $model2->tasks;
        $this->assertCount(1, $model2Tasks);
        $this->assertTrue($model2Tasks->contains('id', $task3->id));
    }

    public function test_tasks_relationship_returns_empty_collection_when_no_tasks(): void
    {
        $model = new ModelWithTasks;
        $model->name = 'Test Model';
        $model->save();

        $tasks = $model->tasks;

        $this->assertCount(0, $tasks);
        $this->assertInstanceOf(Collection::class, $tasks);
    }
}

class ModelWithTasks extends Model
{
    use HasTasks;

    protected $table = 'has_tasks_models';

    protected $fillable = ['name'];
}
