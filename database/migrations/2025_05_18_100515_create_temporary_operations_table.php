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
        Schema::create('temporary_operations', function (Blueprint $table) {
            $table->id();
            $table->string('order_type');
            $table->foreignId('order_id');
            $table->foreignId('production_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('workstation_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description');
            $table->date('operation_date');
            $table->integer('machine_setup_time')->default(0);
            $table->integer('machine_run_time')->default(0);
            $table->integer('labor_setup_time')->default(0);
            $table->integer('labor_run_time')->default(0);
            $table->string('status')->default('created'); 
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temporary_operations');
    }
};
