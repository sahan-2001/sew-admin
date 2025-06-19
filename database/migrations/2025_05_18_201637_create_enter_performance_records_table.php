<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnterPerformanceRecordsTable extends Migration
{
    public function up()
    {
        Schema::create('enter_performance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assign_daily_operation_id');
            $table->unsignedBigInteger('assign_daily_operation_line_id');
            $table->date('operation_date');
            $table->time('operated_time_from');
            $table->time('operated_time_to');
            $table->decimal('actual_machine_setup_time', 10, 2);
            $table->decimal('actual_machine_run_time', 10, 2);
            $table->decimal('actual_employee_setup_time', 10, 2);
            $table->decimal('actual_employee_run_time', 10, 2);
            $table->string('status')->default('pending');
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('enter_performance_records');
    }
}