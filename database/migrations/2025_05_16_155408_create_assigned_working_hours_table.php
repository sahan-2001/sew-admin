<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssignedWorkingHoursTable extends Migration
{
    public function up()
    {
        Schema::create('assigned_working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assign_daily_operation_id')->constrained()->onDelete('cascade');
            $table->date('operation_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('assigned_working_hours');
    }
}