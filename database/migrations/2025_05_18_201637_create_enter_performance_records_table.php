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
            $table->string('order_type');
            $table->unsignedBigInteger('order_id');
            $table->json('performance_records');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('enter_performance_records');
    }
}