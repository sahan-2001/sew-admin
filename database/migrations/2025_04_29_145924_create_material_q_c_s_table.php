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
            $table->decimal('inspected_quantity');
            $table->decimal('approved_qty', 10, 2)->default(0);
            $table->decimal('returned_qty', 10, 2)->default(0);
            $table->decimal('scrapped_qty', 10, 2)->default(0);
            $table->decimal('cost_of_item', 10, 2);
            $table->unsignedBigInteger('store_location_id');
            $table->unsignedBigInteger('register_arrival_id');
            $table->unsignedBigInteger('inspected_by');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('register_arrival_id')->references('id')->on('register_arrivals')->onDelete('cascade');
            $table->foreign('store_location_id')->references('id')->on('inventory_locations')->onDelete('cascade');
            $table->foreign('inspected_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('material_qcs');
    }
}