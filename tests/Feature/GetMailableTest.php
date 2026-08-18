<?php

namespace CheeseDriven\LaravelTasks\Tests\Feature;

use CheeseDriven\LaravelTasks\Enums\TaskLogStatus;
use CheeseDriven\LaravelTasks\Enums\TaskType;
use CheeseDriven\LaravelTasks\Models\Task;
use CheeseDriven\LaravelTasks\TestMail;
use CheeseDriven\LaravelTasks\Tests\TestCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Schema;

class GetMailableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('mailable_target_models')) {
            Schema::create('mailable_target_models', function ($table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }
    }

    public function test_get_mailable_returns_unserialized_mailable(): void
    {
        $task = $this->mailTask(new TestMail);

        $this->assertInstanceOf(TestMail::class, $task->getMailable());
    }

    public function test_get_mailable_recovers_when_a_stored_relation_no_longer_exists(): void
    {
        $model = new MailableTargetModel;
        $model->name = 'Target';
        $model->save();

        $task = $this->mailTask(new TestMailWithModel($model));

        $task->mailable = str_replace(
            's:9:"relations";a:0:{}',
            's:9:"relations";a:1:{i:0;s:7:"missing";}',
            $task->mailable,
        );

        $mailable = $task->getMailable();

        $this->assertInstanceOf(TestMailWithModel::class, $mailable);
        $this->assertTrue($mailable->model->is($model));
        $this->assertNotEquals(TaskLogStatus::Failed->value, $task->fresh()->latest_status);
    }

    public function test_get_mailable_logs_failure_when_unserialize_fails(): void
    {
        $task = $this->mailTask(new TestMail);
        $task->mailable = 'O:21:"App\\Missing\\Mailable":0:{}';

        $this->assertNull($task->getMailable());
        $this->assertEquals(TaskLogStatus::Failed->value, $task->fresh()->latest_status);
        $this->assertDatabaseHas('task_logs', [
            'task_id' => $task->id,
            'status' => TaskLogStatus::Failed->value,
        ]);
    }

    protected function mailTask(MailableContract $mailable): Task
    {
        $task = Task::init('test-mail-task');
        $task->type(TaskType::Mail);
        $task->mailable($mailable);
        $task->recipients('test@example.com');
        $task->save();

        return $task;
    }
}

class MailableTargetModel extends Model
{
    protected $table = 'mailable_target_models';

    protected $guarded = [];
}

class TestMailWithModel extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Model $model) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Test Mail With Model',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>Test</p>',
        );
    }
}
