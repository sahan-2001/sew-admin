<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->unsignedBigInteger('inventory_vat_group_id')->nullable();
            $table->decimal('inventory_vat_rate', 5, 2)->default(0);
            $table->integer('quantity')->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('item_subtotal', 12, 2)->default(0);
            $table->decimal('item_vat_amount', 12, 2)->default(0);
            $table->decimal('item_grand_total', 12, 2)->default(0);
            $table->integer('arrived_quantity')->default(0); 
            $table->integer('remaining_quantity')->default(0);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_order_items');
    }
}