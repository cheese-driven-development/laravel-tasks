<?php

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
        Schema::create(config('tasks.tables.tasks', 'tasks'), function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();
            $table->boolean('unique')->default(false);
            $table->longText('action')->nullable();
            $table->longText('mailable')->nullable();
            $table->json('recipients')->nullable();
            $table->json('constraints')->nullable();
            $table->string('targetable_type')->nullable();
            $table->unsignedBigInteger('targetable_id')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->enum('latest_status', TaskLogStatus::values())->nullable();
            $table->timestamps();
        });

        Schema::create(config('tasks.tables.logs', 'task_logs'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->enum('status', TaskLogStatus::values())->default(TaskLogStatus::Pending->value);
            $table->text('message')->nullable();
            $table->timestamps();

            $table->foreign('task_id')
                ->references('id')
                ->on(config('tasks.tables.tasks', 'tasks'))
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('tasks.tables.logs', 'task_logs'));
        Schema::dropIfExists(config('tasks.tables.tasks', 'tasks'));
    }
};
