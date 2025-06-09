<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVariationItemsTable extends Migration
{
    public function up()
    {
        Schema::create('variation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_order_description_id')->constrained()->onDelete('cascade'); // Foreign key for relationship
            $table->string('variation_name'); 
            $table->integer('quantity'); // Add the quantity column
            $table->decimal('price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('variation_items');
    }
}