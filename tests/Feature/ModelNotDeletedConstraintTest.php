<?php

namespace CheeseDriven\LaravelTasks\Tests\Feature;

use CheeseDriven\LaravelTasks\Actions\LogSomethingAction;
use CheeseDriven\LaravelTasks\Constraints\ModelNotDeletedConstraint;
use CheeseDriven\LaravelTasks\Enums\TaskType;
use CheeseDriven\LaravelTasks\Models\Task;
use CheeseDriven\LaravelTasks\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class ModelNotDeletedConstraintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // create the test table if it doesn't exist
        if (! Schema::hasTable('soft_deletable_models')) {
            Schema::create('soft_deletable_models', function ($table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('non_soft_deletable_models')) {
            Schema::create('non_soft_deletable_models', function ($table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }
    }

    public function test_should_run_with_soft_deletable_model(): void
    {
        $model = new SoftDeletableModel;
        $model->name = 'Test Model';
        $model->save();

        $constraint = new ModelNotDeletedConstraint($model);

        $task = Task::init('test-task');
        $task->type(TaskType::Custom);
        $task->action(new LogSomethingAction('Test'));
        $task->constraint($constraint);
        $task->save();

        // model is not trashed yet, should return true
        $this->assertTrue($constraint->shouldRun($task));
        $this->assertTrue($task->passesCustomConstraints());

        // trash the model
        $model->delete();

        // model is trashed, should return false
        $this->assertFalse($constraint->shouldRun($task));
        $this->assertFalse($task->passesCustomConstraints());
        $this->assertSoftDeleted('soft_deletable_models', ['id' => $model->id]);
    }

    public function test_should_run_with_non_soft_deletable_model(): void
    {
        $model = new NonSoftDeletableModel;
        $model->name = 'Test Model';
        $model->save();

        $constraint = new ModelNotDeletedConstraint($model);

        $task = Task::init('test-task');
        $task->type(TaskType::Custom);
        $task->action(new LogSomethingAction('Test'));
        $task->constraint($constraint);
        $task->save();

        // model is not trashed yet, should return true
        $this->assertTrue($constraint->shouldRun($task));
        $this->assertTrue($task->passesCustomConstraints());

        // trash the model
        $model->delete();

        // model is trashed, should return false
        $this->assertFalse($constraint->shouldRun($task));
        $this->assertFalse($task->passesCustomConstraints());
        $this->assertDatabaseMissing('non_soft_deletable_models', ['id' => $model->id]);
    }
}

class SoftDeletableModel extends Model
{
    use SoftDeletes;

    protected $table = 'soft_deletable_models';

    protected $fillable = ['name'];
}

class NonSoftDeletableModel extends Model
{
    protected $table = 'non_soft_deletable_models';

    protected $fillable = ['name'];
}
