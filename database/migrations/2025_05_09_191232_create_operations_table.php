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
        Schema::create('operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workstation_id')->constrained();
            $table->text('description');
            $table->string('status')->default('active');
            $table->foreignId('employee_id')->nullable()->constrained('users');
            $table->foreignId('supervisor_id')->nullable()->constrained('users');
            $table->foreignId('third_party_service_id')->nullable()->constrained('third_party_services');
            $table->foreignId('machine_id')->nullable()->constrained('production_machines');
            $table->integer('machine_setup_time')->default(0);
            $table->integer('machine_run_time')->default(0);
            $table->integer('labor_setup_time')->default(0);
            $table->integer('labor_run_time')->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operations');
    }
};
