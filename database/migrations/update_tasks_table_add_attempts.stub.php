<?php

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
        $table = config('tasks.tables.tasks', 'tasks');

        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'attempts')) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            $table->unsignedInteger('attempts')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $table = config('tasks.tables.tasks', 'tasks');

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'attempts')) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            $table->dropColumn('attempts');
        });
    }
};
