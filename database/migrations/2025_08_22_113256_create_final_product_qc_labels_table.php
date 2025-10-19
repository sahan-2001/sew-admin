<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('final_product_qc_labels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('final_product_qc_id')->constrained()->cascadeOnDelete();;
            $table->unsignedBigInteger('cutting_label_id');
            $table->enum('result', ['pass', 'fail']);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('cutting_label_id')->references('id')->on('cutting_labels')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('final_product_qc_labels');
    }
};