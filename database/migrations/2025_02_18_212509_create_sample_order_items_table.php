<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sample_order_items', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('sample_order_id'); // Foreign key to sample_orders
            $table->string('item_name'); // Item name
            $table->string('variation_name')->nullable(); // Variation name (if any)
            $table->text('note')->nullable(); // Notes for the item
            $table->integer('quantity')->nullable(); // Quantity of the item
            $table->decimal('price', 10, 2)->nullable(); // Price of the item
            $table->decimal('total', 10, 2)->nullable(); // Total price for the item
            $table->boolean('is_variation')->default(0); // Indicator if it's a variation
            $table->timestamps(); // Created_at and updated_at timestamps

            // Foreign key constraint
            $table->foreign('sample_order_id')
                  ->references('order_id')
                  ->on('sample_orders')
                  ->onDelete('cascade'); // Deletes items if the associated sample order is deleted
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sample_order_items');
    }
};
