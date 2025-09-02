<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up()
    {
        Schema::create('final_product_qcs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('final_product_qc_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cutting_label_id')->constrained()->cascadeOnDelete();
            $table->enum('result', ['passed', 'failed'])->default('passed');
            $table->foreignId('created_by');
            $table->foreignId('updated_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('final_product_qcs');
    }
};