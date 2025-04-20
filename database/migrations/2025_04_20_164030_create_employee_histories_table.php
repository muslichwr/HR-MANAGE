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
        Schema::create('employee_histories', function (Blueprint $table) {
            $table->id('history_id');
            $table->foreignId('employee_id')->constrained('employees', 'employee_id');
            $table->foreignId('old_position_id')->nullable()->constrained('positions', 'position_id');
            $table->foreignId('new_position_id')->nullable()->constrained('positions', 'position_id');
            $table->foreignId('old_department_id')->nullable()->constrained('departments', 'department_id');
            $table->foreignId('new_department_id')->nullable()->constrained('departments', 'department_id');
            $table->foreignId('changed_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_histories');
    }
};
