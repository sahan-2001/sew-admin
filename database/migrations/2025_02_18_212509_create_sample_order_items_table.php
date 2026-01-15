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
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->id(); 
            $table->unsignedBigInteger('sample_order_id'); 
            $table->string('item_name');
            $table->string('variation_name')->nullable();
            $table->text('note')->nullable(); 
            $table->integer('quantity')->nullable(); 
            $table->decimal('price', 10, 2)->nullable(); 
            $table->decimal('total', 10, 2)->nullable(); 
            $table->boolean('is_variation')->default(0); 
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps(); 

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
