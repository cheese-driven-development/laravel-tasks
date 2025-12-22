<?php

namespace CheeseDriven\LaravelTasks\Tests\Feature;

use CheeseDriven\LaravelTasks\Actions\LogSomethingAction;
use CheeseDriven\LaravelTasks\Constraints\TargetableNotDeletedConstraint;
use CheeseDriven\LaravelTasks\Enums\TaskType;
use CheeseDriven\LaravelTasks\Models\Task;
use CheeseDriven\LaravelTasks\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class TargetableNotDeletedConstraintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // create the test tables if they don't exist
        if (! Schema::hasTable('soft_deletable_targets')) {
            Schema::create('soft_deletable_targets', function ($table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('non_soft_deletable_targets')) {
            Schema::create('non_soft_deletable_targets', function ($table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }
    }

    public function test_should_run_without_targetable(): void
    {
        $constraint = new TargetableNotDeletedConstraint;

        $task = Task::init('test-task');
        $task->type(TaskType::Custom);
        $task->action(new LogSomethingAction('Test'));
        $task->constraint($constraint);
        $task->save();

        // task has no targetable, should return true
        $this->assertTrue($constraint->shouldRun($task));
        $this->assertTrue($task->passesCustomConstraints());
    }

    public function test_should_run_with_soft_deletable_target(): void
    {
        $target = new SoftDeletableTarget;
        $target->name = 'Test Target';
        $target->save();

        $constraint = new TargetableNotDeletedConstraint;

        $task = Task::init('test-task');
        $task->type(TaskType::Custom);
        $task->action(new LogSomethingAction('Test'));
        $task->target($target);
        $task->constraint($constraint);
        $task->save();

        // target is not trashed, should return true
        $this->assertTrue($constraint->shouldRun($task));
        $this->assertTrue($task->passesCustomConstraints());

        // soft delete the target
        $target->delete();

        // target is soft deleted, should return false
        $this->assertFalse($constraint->shouldRun($task));
        $this->assertFalse($task->passesCustomConstraints());
        $this->assertSoftDeleted('soft_deletable_targets', ['id' => $target->id]);
    }

    public function test_should_run_with_non_soft_deletable_target(): void
    {
        $target = new NonSoftDeletableTarget;
        $target->name = 'Test Target';
        $target->save();

        $constraint = new TargetableNotDeletedConstraint;

        $task = Task::init('test-task');
        $task->type(TaskType::Custom);
        $task->action(new LogSomethingAction('Test'));
        $task->target($target);
        $task->constraint($constraint);
        $task->save();

        // non-soft deletable target exists, should return true
        $this->assertTrue($constraint->shouldRun($task));
        $this->assertTrue($task->passesCustomConstraints());

        // delete the target
        $target->delete();

        // target is deleted, should return false
        $this->assertFalse($constraint->shouldRun($task));
        $this->assertFalse($task->passesCustomConstraints());
        $this->assertDatabaseMissing('non_soft_deletable_targets', ['id' => $target->id]);
    }
}

class SoftDeletableTarget extends Model
{
    use SoftDeletes;

    protected $table = 'soft_deletable_targets';

    protected $fillable = ['name'];
}

class NonSoftDeletableTarget extends Model
{
    protected $table = 'non_soft_deletable_targets';

    protected $fillable = ['name'];
}
