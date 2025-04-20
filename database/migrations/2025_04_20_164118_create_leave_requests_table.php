<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id('request_id');
            $table->foreignId('employee_id')->constrained('employees', 'employee_id');
            $table->foreignId('leave_type_id')->constrained('leave_types', 'leave_type_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approver_id')->nullable()->constrained('users');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        // Tambahkan check constraint menggunakan raw SQL
        DB::statement('ALTER TABLE leave_requests ADD CONSTRAINT check_end_date_after_start_date CHECK (end_date >= start_date)');
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};