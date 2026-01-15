<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnterSupervisorPerformancesTable extends Migration
{
    public function up()
    {
        Schema::create('enter_supervisor_performances', function (Blueprint $table) {
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->id();
            $table->unsignedBigInteger('enter_performance_record_id');
            $table->unsignedBigInteger('supervisor_id');
            $table->integer('accepted_qty');
            $table->integer('rejected_qty');
            $table->integer('supervisored_qty');
            $table->decimal('sup_downtime', 10, 2);
            $table->text('sup_notes')->nullable();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('enter_supervisor_performances');
    }
}