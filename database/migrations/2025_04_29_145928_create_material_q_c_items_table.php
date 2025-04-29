<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaterialQCItemsTable extends Migration
{
    public function up()
    {
        Schema::create('material_qc_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_qc_id');
            $table->unsignedBigInteger('item_id');
            $table->integer('quantity');
            $table->string('status')->default('to be inspected');
            $table->timestamps();

            $table->foreign('material_qc_id')->references('id')->on('material_qcs')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('inventory_items')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('material_qc_items');
    }
}