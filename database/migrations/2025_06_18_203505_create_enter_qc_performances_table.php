<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnterQcPerformancesTable extends Migration
{
    public function up()
    {
        Schema::create('enter_qc_performances', function (Blueprint $table) {
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->id();
            $table->unsignedBigInteger('enter_performance_record_id');
            $table->integer('no_of_passed_items');
            $table->integer('no_of_failed_items');
            $table->string('action_type', 50)->nullable();
            $table->unsignedBigInteger('cutting_station_id')->nullable();
            $table->unsignedBigInteger('assign_operation_line_id')->nullable();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('enter_qc_performances');
    }
}