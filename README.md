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
}
```

In this example, the task will only be executed if the target is active.

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

Lists all tasks with their status, type, and scheduled execution time:

```bash
php artisan tasks:list
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