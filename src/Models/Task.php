<?php

namespace CheeseDriven\LaravelTasks\Models;

use CheeseDriven\LaravelTasks\Actions\TaskCompletedAction;
use CheeseDriven\LaravelTasks\Contracts\Action;
use CheeseDriven\LaravelTasks\Contracts\Constraint;
use CheeseDriven\LaravelTasks\Contracts\WithConstraints;
use CheeseDriven\LaravelTasks\Enums\TaskLogStatus;
use CheeseDriven\LaravelTasks\Enums\TaskType;
use CheeseDriven\LaravelTasks\Jobs\SendMailJob;
use CheeseDriven\LaravelTasks\Support\ClassResolver;
use CheeseDriven\LaravelTasks\Support\ConstraintsResolver;
use Exception;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Throwable;
use TypeError;

class Task extends Model
{
    use ClassResolver;
    use ConstraintsResolver;

    public function getTable()
    {
        return Config::get('tasks.tables.tasks', 'tasks');
    }

    protected $guarded = [];

    protected $casts = [
        'unique' => 'boolean',
        'action' => 'string',
        'mailable' => 'string',
        'recipients' => 'array',
        'constraints' => 'array',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(static::taskLogModel());
    }

    public function targetable(): MorphTo
    {
        return $this->morphTo();
    }

    public function target(Model $target): self
    {
        $this->targetable_type = $target->getMorphClass();
        $this->targetable_id = $target->getKey();

        return $this;
    }

    /**
     * Only include tasks that have not been executed before.
     */
    #[Scope]
    public function notCompleted(Builder $query): void
    {
        $query->whereNull('completed_at');
    }

    /**
     * Initialize a new task.
     */
    public static function init(string $name): self
    {
        return new static(['name' => $name]);
    }

    /**
     * Set the type of the task.
     */
    public function type(TaskType $type): self
    {
        $this->type = $type->value;

        return $this;
    }

    /**
     * Set the execution date of the task.
     */
    public function scheduleAt(Carbon $date): self
    {
        $this->scheduled_at = $date;

        return $this;
    }

    public function recipients(...$recipients): self
    {
        $this->recipients = array_merge(collect($recipients)
            ->flatten()
            ->filter(fn ($email) => $this->ensureValidEmail($email))
            ->unique()
            ->toArray(), $this->recipients ?? []);

        return $this;
    }

    public function log(TaskLogStatus $status, ?string $message = null): self
    {
        $this->logs()->create([
            'status' => $status->value,
            'message' => $message,
        ]);

        $this->update(['latest_status' => $status->value]);

        return $this;
    }

    public function logPending(?string $message = null): self
    {
        return $this->log(TaskLogStatus::Pending, $message);
    }

    public function logSuccess(?string $message = null): self
    {
        return $this->log(TaskLogStatus::Success, $message);
    }

    public function logFailure(string $exception): self
    {
        return $this->log(TaskLogStatus::Failed, $exception);
    }

    public function getRecipients(): array
    {
        return collect($this->recipients)
            ->filter(fn ($email) => $this->ensureValidEmail($email))
            ->toArray();
    }

    public function getMailable(): ?Mailable
    {
        try {
            return unserialize($this->mailable);
        } catch (Throwable|TypeError $e) {
            $this->logFailure($e->getMessage());
        }

        return null;
    }

    protected function ensureValidEmail(string $email): bool
    {
        return ! Validator::make(
            compact('email'),
            ['email' => 'required|email']
        )->fails();
    }

    /**
     * Add a constraint to the task.
     */
    public function constraint(Constraint $constraint): self
    {
        $this->constraints = array_merge(Arr::wrap($this->constraints), [serialize($constraint)]);

        return $this;
    }

    /**
     * Set the mailable of the task.
     */
    public function mailable(Mailable $mailable): self
    {
        $this->mailable = serialize($mailable);

        if ($mailable instanceof WithConstraints) {
            // add constraints from the mailable to the task
            collect($mailable->constraints())
                ->filter(fn ($constraint) => $constraint instanceof Constraint)
                ->each(fn (Constraint $constraint) => $this->constraint($constraint));
        }

        return $this;
    }

    public function action(Action $action): self
    {
        $this->action = serialize($action);

        return $this;
    }

    public function unique(): self
    {
        $this->unique = true;

        return $this;
    }

    public function isUnique(): bool
    {
        return (bool) $this->unique;
    }

    /**
     * Check if the task should run.
     */
    public function shouldRun(): bool
    {
        try {
            if (! $this->passesDefaultConstraints()) {
                return false;
            }

            if (! $this->passesCustomConstraints()) {
                return false;
            }

            return true;

        } catch (Exception|Throwable $e) {
            $this->logFailure($e->getMessage());

            return false;
        }
    }

    protected function checkRequiredMethodCalls()
    {
        if (empty($this->attributes['type'])) {
            throw new Exception('Type must be set for tasks');
        }

        if ($this->attributes['type'] === TaskType::Mail->value) {
            if (empty($this->attributes['mailable'])) {
                throw new Exception('Mailable must be set for mail tasks');
            }

            if (empty($this->attributes['recipients']) || count($this->getRecipients()) === 0) {
                throw new Exception('Recipients must be set for mail tasks');
            }
        }

        if ($this->attributes['type'] === TaskType::Custom->value) {
            if (empty($this->attributes['action'])) {
                throw new Exception('Action must be set for custom tasks');
            }
        }
    }

    public function save(array $options = [])
    {
        $this->checkRequiredMethodCalls();

        if (! $this->isUnique()) {
            return parent::save($options);
        }

        // make sure the task is unique by target, action, mailable and name
        $exists = static::where('targetable_type', $this->targetable_type)
            ->where('targetable_id', $this->targetable_id)
            ->where('action', $this->action)
            ->where('mailable', $this->mailable)
            ->where('name', $this->name)
            ->where($this->getKeyName(), '!=', $this->getKey())
            ->exists();

        if ($exists) {
            return false;
        }

        return parent::save($options);
    }

    public function execute(): void
    {
        if (! $this->save()) {
            return;
        }

        try {
            if ($this->type === TaskType::Mail->value) {
                $this->logPending('Dispatching mail job...');
                dispatch(new SendMailJob($this));
            } elseif (isset($this->action)) {
                $this->logPending('Handling custom action...');
                unserialize($this->action)->handle($this);
            }
        } catch (Exception|Throwable $e) {
            $this->logFailure($e->getMessage());
        }
    }

    public function complete(): void
    {
        if ($this->latest_status !== TaskLogStatus::Success->value) {
            $this->logSuccess('Task completed successfully');
        }

        (new TaskCompletedAction)->handle($this);
    }
}
