<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaterialQCsTable extends Migration
{
    public function up()
    {
        Schema::create('material_qcs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('item_id');
            $table->integer('inspected_quantity');
            $table->integer('returned_qty')->default(0);
            $table->integer('scrapped_qty')->default(0);
            $table->decimal('cost_of_item', 10, 2);
            $table->unsignedBigInteger('inspected_by');
            $table->unsignedBigInteger('created_by');
            $table->string('status')->default('to be inspected');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('inspected_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('material_qcs');
    }
}