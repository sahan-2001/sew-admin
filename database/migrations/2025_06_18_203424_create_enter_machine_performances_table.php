<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnterMachinePerformancesTable extends Migration
{
    public function up()
    {
        Schema::create('enter_machine_performances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enter_performance_record_id');
            $table->unsignedBigInteger('machine_id');
            $table->decimal('machine_downtime', 10, 2);
            $table->text('machine_notes')->nullable();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('enter_machine_performances');
    }
}