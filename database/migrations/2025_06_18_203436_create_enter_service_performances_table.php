<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnterServicePerformancesTable extends Migration
{
    public function up()
    {
        Schema::create('enter_service_performances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enter_performance_record_id');
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('service_process_id');
            $table->decimal('used_amount', 10, 2);
            $table->decimal('unit_rate', 10, 2);
            $table->decimal('total_cost', 10, 2);
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('enter_service_performances');
    }
}