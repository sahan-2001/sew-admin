<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryItemsTable extends Migration
{
    public function up()
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->string('name');
            $table->string('category');
            $table->text('special_note')->nullable();
            $table->string('uom');
            $table->integer('available_quantity')->default(0);
            $table->decimal('moq', 12, 2)->nullable();
            $table->decimal('max_order_quantity', 12, 2)->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['created_by']); // Drop foreign key constraint
        });
        Schema::dropIfExists('inventory_items');
    }
}