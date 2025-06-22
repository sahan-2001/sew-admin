<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('end_of_day_report_operations', function (Blueprint $table) {
    $table->engine = 'InnoDB';

    $table->id();
    
    $table->foreignId('end_of_day_report_id');
    $table->foreignId('enter_performance_record_id');
    $table->foreignId('assign_daily_operation_id');
    $table->foreignId('operation_line_id');

    $table->unsignedBigInteger('created_by')->nullable();
    $table->unsignedBigInteger('updated_by')->nullable();

    $table->timestamps();
    $table->softDeletes();
});

    }

    public function down(): void
    {
        Schema::dropIfExists('end_of_day_report_operations');
    }
};