<?php

use CheeseDriven\LaravelTasks\Enums\TaskLogStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        Schema::table(config('tasks.tables.tasks', 'tasks'), function (Blueprint $table) {
            $table->enum('latest_status', TaskLogStatus::values())->nullable()->change();
        });

        Schema::table(config('tasks.tables.logs', 'task_logs'), function (Blueprint $table) {
            $table->enum('status', TaskLogStatus::values())->default(TaskLogStatus::Pending->value)->change();
        });
    }

    public function down(): void
    {
        // skip, do not remove enum values
    }
};
