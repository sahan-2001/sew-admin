<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('assign_daily_operation_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assign_daily_operation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cutting_label_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();
            
            // Add any additional fields you might need
            $table->unique(['assign_daily_operation_id', 'cutting_label_id'], 'operation_label_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('assign_daily_operation_labels');
    }
};