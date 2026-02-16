# Laravel Tasks

Create and manage scheduled tasks in Laravel.

## Installation

```bash
composer require cheese-driven-development/laravel-tasks
```

### Publish the configuration and migrations

```bash
php artisan vendor:publish --provider="CheeseDriven\LaravelTasks\TaskServiceProvider" --tag="laravel-tasks-config"
php artisan vendor:publish --provider="CheeseDriven\LaravelTasks\TaskServiceProvider" --tag="laravel-tasks-migrations"
```

### Run the migrations

```bash
php artisan migrate
```

## Usage

### Task Types

There are two types of tasks:

- Mail Task
- Custom Action Task

### Mail Task

A mail task is a task that sends a Laravel Mailable.

```php
use CheeseDriven\LaravelTasks\Task;
use CheeseDriven\LaravelTasks\Enums\TaskType;
use App\Mail\WelcomeMail;

Task::init('Send welcome email')
    ->type(TaskType::Mail)
    ->mailable(new WelcomeMail)
    ->recipients('user@example.com')
    ->scheduleAt(now()->addMinutes(5))
    ->unique()
    ->save();
```

**A note on unique tasks:**

The `unique()` method is used to set the task to be unique by target, action, mailable and name. This will allow you to call the task multiple times without creating duplicate tasks.

Every mail task will be pushed to the queue via the `SendMailJob` and executed via the `SendMailAction`. The `SendMailJob` will always only be dispatched once - even if the task itself is executed multiple times. This will prevent duplicate jobs from being added to the queue. So only one `SendMailAction` will be executed for a given mail task.

### Custom Action Task

A custom action task is a task that executes a custom action.

```php
use CheeseDriven\LaravelTasks\Task;
use CheeseDriven\LaravelTasks\Enums\TaskType;
use App\Actions\ProcessOrderAction;

Task::init('Process order')
    ->type(TaskType::Custom)
    ->action(new ProcessOrderAction)
    ->scheduleAt(now()->addHour())
    ->unique()
    ->save();
```

### Creating Tasks

You can create tasks using the `Task` facade. This is the basic requirement for a task to be executed:

```php
use CheeseDriven\LaravelTasks\Task;
use CheeseDriven\LaravelTasks\Enums\TaskType;

Task::init('Mailable Task')
    ->type(TaskType::Mail)
    ->mailable(...)
    ->recipients(...)
    ->save();

Task::init('Custom Action Task')
    ->type(TaskType::Custom)
    ->action(...)
    ->save();
```

The following methods are available for creating tasks:

- `type(TaskType $type)`: Set the type of the task.
- `mailable(Mailable $mailable)`: Set the mailable of the task (only for Mail Tasks).
- `recipients(...$recipients)`: Set the recipients of the task (only for Mail Tasks).
- `action(Action $action)`: Set the action of the task (only for Custom Action Tasks).
- `scheduleAt(Carbon $date)`: Set the scheduled execution time of the task.
- `unique()`: Set the task to be unique. To prevent duplicate tasks from being created.
- `target(Model $target)`: Set the target of the task. To attach a model to the task.
- `save()`: Save the task to the database. This is required for the task to be executed.

### Custom Actions

Custom actions are used to execute a custom action. You can add a custom action to a task by using the `action(Action $action)` method.

```php
use CheeseDriven\LaravelTasks\Task;
use CheeseDriven\LaravelTasks\Enums\TaskType;
use App\Actions\ProcessOrderAction;

Task::init('Process order')
    ->type(TaskType::Custom)
    ->action(new ProcessOrderAction)
    ->save();
```

The action must implement the `Action` interface. The `handle` method should receive the task as a parameter.

```php
namespace App\Actions;

use CheeseDriven\LaravelTasks\Contracts\Action;

class ProcessOrderAction implements Action
{
    public function handle(Task $task)
    {
        // ... your custom action logic here ...
    }
}
```

### Custom Constraints

Constraints are used to restrict if a task should be executed. You can add constraints to a task by using the `constraint(Constraint $constraint)` method.

```php
use CheeseDriven\LaravelTasks\Task;
use CheeseDriven\LaravelTasks\Enums\TaskType;
use App\Constraints\MyCustomConstraint;

Task::init('Process order')
    ->type(TaskType::Custom)
    ->action(new ProcessOrderAction)
    ->constraint(new MyCustomConstraint)
    ->save();
```

Constraints must implement the `Constraint` interface. The `shouldRun` method should return a boolean value.

```php
namespace App\Constraints;

use CheeseDriven\LaravelTasks\Contracts\Constraint;

class MyCustomConstraint implements Constraint
{
    public function shouldRun(Task $task): bool
    {
        return $task->targetable->isActive();
    }

    public function completeOnSkipped(): bool
    {
        return false;
    }
}
```

In the above example, the task will only be executed if the target is active. It will not be marked as completed when the constraint fails. So the task will be triggered again with the next task run.

The `completeOnSkipped` method is used to determine if the task should be marked as completed when the constraint fails. If true, the task will be completed after the task is skipped when the constraint fails.

```php
namespace App\Constraints;

use CheeseDriven\LaravelTasks\Contracts\Constraint;

class MyCustomConstraint implements Constraint
{
    public function shouldRun(Task $task): bool
    {
        // ... your custom constraint logic here ...
    }

    public function completeOnSkipped(): bool
    {
        return true;
    }
}
```

For example if a model is deleted, it doesn't make sense to run the task again. So you can mark the task as completed when the constraint fails.

You can add multiple constraints to a task by using the `constraint(Constraint $constraint)` method multiple times.

```php
Task::init('Process order')
    ->type(TaskType::Custom)
    ->action(new ProcessOrderAction)
    ->constraint(new MyCustomConstraint)
    ->constraint(new MyOtherCustomConstraint)
    ->save();
```

You can also add constraints to a mailable by implementing the `WithConstraints` interface in the mailable class. Instead of declaring the constraints in the task, you can declare them directly in the mailable class.

```php
namespace App\Mail;

use CheeseDriven\LaravelTasks\Contracts\WithConstraints;

class MyCustomMailable implements WithConstraints
{
    public function constraints(): array
    {
        return [
            new MyCustomConstraint,
            new MyOtherCustomConstraint,
        ];
    }
}
```

In this example, the task will only be executed if these custom constraints are met.

### Task Execution

Tasks are executed by the `tasks:run` command. This command should be scheduled in your `app/Console/Kernel.php`:

If a task has no scheduled execution time, it will be executed immediately.

### Seeding Sample Tasks

To see examples of how tasks work, you can seed some sample tasks:

```bash
php artisan tasks:seed
```

## Commands

The package provides several Artisan commands:

### `tasks:run`

Runs all tasks that are ready to be executed. This command should be scheduled in your `app/Console/Kernel.php`:

```php
use CheeseDriven\LaravelTasks\Commands\RunTasksCommand;

protected function schedule(Schedule $schedule): void
{
    $schedule->command(RunTasksCommand::class)->everyThirtyMinutes();
    // or use smaller intervals like:
    // $schedule->command(RunTasksCommand::class)->everyMinute();
    // $schedule->command(RunTasksCommand::class)->everyFiveMinutes();
}
```

### `tasks:list`

Lists tasks with their status, type, and scheduled execution time. By default, it only lists tasks that have not been executed successfully yet:

```bash
php artisan tasks:list
```

To include all tasks, including successful runs:

```bash
php artisan tasks:list --all
```

### `tasks:prune`

Removes old tasks and their logs. By default, it removes tasks older than 30 days:

```bash
php artisan tasks:prune
```

You can specify a different number of days:

```bash
php artisan tasks:prune --days=60
```

This will remove all tasks and their logs older than 60 days.

## Scheduler Setup

**Important:** Make sure to add the `tasks:run` command to your Laravel scheduler in `app/Console/Kernel.php`:

```php
use CheeseDriven\LaravelTasks\Commands\RunTasksCommand;

protected function schedule(Schedule $schedule): void
{
    $schedule->command(RunTasksCommand::class)->everyThirtyMinutes();
}
```

For more frequent task execution, you can use smaller intervals:

```php
$schedule->command(RunTasksCommand::class)->everyMinute();
// or
$schedule->command(RunTasksCommand::class)->everyFiveMinutes();
```

### Upgrade Guide

#### From v.1.0.7-alpha to v.1.1.0-alpha

This release eliminates the use of enums for the `latest_status` and `status` columns for wider database support and future compatibility. You should manually migrate the fields to the new data type to avoid breaking changes in the future (if new statuses are added).

To do this, you should create a new migration in your project:

```php
php artisan make:migration change_latest_status_and_status_to_string --table=tasks
```

and add the following code to the migration:

```php
use CheeseDriven\LaravelTasks\Enums\TaskLogStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('latest_status')->nullable()->change();
        });

        Schema::table('task_logs', function (Blueprint $table) {
            $table->string('status')->default(TaskLogStatus::Pending->value)->change();
        });
    }
};
```

#### From v.1.0.6-alpha to v.1.0.7-alpha

- Custom constraints need to implement the `completeOnSkipped` method.
- Republish the migrations to add the `skipped` status to the tasks table.

```php
php artisan vendor:publish --provider="CheeseDriven\LaravelTasks\TaskServiceProvider" --tag="laravel-tasks-migrations"
```

### Testing

```bash
composer test
```

### Security

If you discover any security related issues, please email melting@cheesedriven.dev instead of using the issue tracker.

### Credits

This package is heavily inspired by [BinarCode/laravel-mailator](https://github.com/BinarCode/laravel-mailator). Thanks to [Eduard Lupacescu](https://github.com/binaryk) for the original idea and implementation.