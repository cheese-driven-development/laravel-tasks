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
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Throwable;

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
        'attempts' => 'integer',
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
        if ($this->latest_status === $status->value) {
            return $this;
        }

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
        $this->log(TaskLogStatus::Failed, $exception);

        $this->incrementAttemptsAndFailPermanentlyIfNeeded();

        return $this;
    }

    public function logExhausted(?string $message = null): self
    {
        return $this->log(
            TaskLogStatus::Exhausted,
            $message ?? "Stopped after {$this->attempts} failed attempts"
        );
    }

    public function failPermanently(?string $message = null): self
    {
        $this->logExhausted($message);

        $this->completed_at = now();
        $this->save();

        return $this;
    }

    public function logSkipped(?string $message = null): self
    {
        return $this->log(TaskLogStatus::Skipped, $message);
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
        } catch (RelationNotFoundException) {
            try {
                return unserialize($this->mailableWithoutRelations());
            } catch (Throwable $exception) {
                $this->logFailure($exception->getMessage());
            }
        } catch (Throwable $exception) {
            $this->logFailure($exception->getMessage());
        }

        return null;
    }

    /**
     * Drop stored relation names so Laravel reloads the model by class and id only.
     */
    protected function mailableWithoutRelations(): string
    {
        return preg_replace(
            '/s:9:"relations";a:\d+:\{[^}]*\}/',
            's:9:"relations";a:0:{}',
            $this->mailable,
        ) ?? $this->mailable;
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

                // only log "skipped" status if the task is not scheduled or the scheduled time
                // is in the past, in order to keep the "scheduled" status for future tasks
                if (empty($this->scheduled_at) || $this->scheduled_at->isPast()) {
                    $this->logSkipped('Task skipped due to default constraints');
                }

                if ($this->shouldCompleteOnDefaultConstraintFailure()) {
                    $this->complete();
                }

                return false;
            }

            if (! $this->passesCustomConstraints()) {
                $this->logSkipped('Task skipped due to custom constraints');

                if ($this->shouldCompleteOnCustomConstraintFailure()) {
                    $this->complete();
                }

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

    public function complete(string $logMessage = 'Task completed successfully'): void
    {
        if ($this->latest_status !== TaskLogStatus::Success->value) {
            $this->logSuccess($logMessage);
        }

        (new TaskCompletedAction)->handle($this);
    }

    protected function incrementAttemptsAndFailPermanentlyIfNeeded(): void
    {
        if ($this->completed_at !== null) {
            return;
        }

        $this->increment('attempts');

        $maxAttempts = Config::get('tasks.max_attempts', 3);

        if ($maxAttempts === null) {
            return;
        }

        if ($this->attempts >= (int) $maxAttempts) {
            $this->failPermanently();
        }
    }
}
