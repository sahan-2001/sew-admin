<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assign_daily_operation_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assign_daily_operation_id');
            $table->unsignedBigInteger('production_line_id');
            $table->unsignedBigInteger('workstation_id');
            $table->unsignedBigInteger('operation_id');
            $table->integer('setup_time')->nullable();
            $table->integer('run_time')->nullable();
            $table->string('target_duration')->nullable();
            $table->integer('target')->nullable();
            $table->string('measurement_unit')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('assign_daily_operation_id')->references('id')->on('assign_daily_operations')->onDelete('cascade');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('assign_daily_operation_lines');
    }
};