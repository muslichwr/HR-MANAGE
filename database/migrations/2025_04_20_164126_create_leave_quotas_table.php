<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_quotas', function (Blueprint $table) {
            $table->id('quota_id');
            $table->foreignId('employee_id')->constrained('employees', 'employee_id');
            $table->foreignId('leave_type_id')->constrained('leave_types', 'leave_type_id');
            $table->smallInteger('year');
            $table->integer('total_quota');
            $table->integer('used_quota')->default(0);
            $table->integer('remaining_quota');
            $table->integer('prorated_quota')->nullable();
            $table->date('reset_date');
            $table->timestamps();
            
            $table->unique(['employee_id', 'leave_type_id', 'year']);
        });

        // Tambahkan check constraint menggunakan raw SQL
        DB::statement('
            ALTER TABLE leave_quotas 
            ADD CONSTRAINT check_remaining_quota_non_negative 
            CHECK (remaining_quota >= 0)
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_quotas');
    }
};