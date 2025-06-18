<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnterEmployeePerformancesTable extends Migration
{
    public function up()
    {
        Schema::create('enter_employee_performances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enter_performance_record_id');
            $table->unsignedBigInteger('employee_id');
            $table->decimal('emp_production', 10, 2);
            $table->decimal('emp_downtime', 10, 2);
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('enter_employee_performances');
    }
}