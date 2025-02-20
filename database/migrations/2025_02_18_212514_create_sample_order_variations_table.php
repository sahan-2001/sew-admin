<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSampleOrderVariationsTable extends Migration
{
    public function up()
    {
        Schema::create('sample_order_variations', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->foreignId('sample_order_item_id')->constrained()->onDelete('cascade'); // Foreign key to sample_order_items
            $table->string('variation_name'); // Variation name
            $table->integer('quantity'); // Quantity for the variation
            $table->decimal('price', 10, 2); // Price for the variation
            $table->decimal('total', 10, 2); // Total price for the variation
            $table->timestamps(); // Created_at and updated_at timestamps
        });
    }

    public function down()
    {
        Schema::dropIfExists('sample_order_variations');
    }
}
