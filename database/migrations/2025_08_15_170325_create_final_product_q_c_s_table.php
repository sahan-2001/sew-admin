<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up()
    {
        Schema::create('final_product_qc_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('final_product_qc_id')->constrained()->onDelete('cascade');
            $table->foreignId('cutting_label_id')->constrained()->onDelete('cascade');
            $table->enum('result', ['passed', 'failed'])->default('passed');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('final_product_qcs');
    }
};