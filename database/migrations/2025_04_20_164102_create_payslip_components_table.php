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
        Schema::create('payslip_components', function (Blueprint $table) {
            $table->id('payslip_component_id');
            $table->foreignId('payslip_id')->constrained('payslips', 'payslip_id');
            $table->foreignId('component_id')->constrained('salary_components', 'component_id');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_components');
    }
};
