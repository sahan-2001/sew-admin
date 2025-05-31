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
        Schema::create('employee_performances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enter_performance_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users');
            $table->decimal('production_quantity', 10, 2)->nullable();
            $table->string('production_unit')->nullable();
            $table->decimal('waste_quantity', 10, 2)->nullable();
            $table->string('waste_unit')->nullable();
            $table->decimal('working_hours', 5, 2)->nullable();
            $table->decimal('efficiency', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_performances');
    }
};
