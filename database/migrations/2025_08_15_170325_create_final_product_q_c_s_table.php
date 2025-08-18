<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up()
    {
        Schema::create('final_product_qcs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cutting_label_id');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('qc_officer_id');
            $table->date('inspected_date')->nullable();
            $table->boolean('result')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('cutting_label_id')->references('id')->on('cutting_labels')->onDelete('cascade');
            $table->foreign('qc_officer_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('final_product_qcs');
    }
};