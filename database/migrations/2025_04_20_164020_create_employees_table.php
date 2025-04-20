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
        Schema::create('employees', function (Blueprint $table) {
            $table->id('employee_id');
            $table->string('nik', 20)->unique();
            $table->string('full_name', 100);
            $table->text('address');
            $table->foreignId('position_id')->constrained('positions', 'position_id');
            $table->foreignId('department_id')->constrained('departments', 'department_id');
            $table->date('join_date');
            $table->enum('status', ['active', 'leave', 'resigned'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
