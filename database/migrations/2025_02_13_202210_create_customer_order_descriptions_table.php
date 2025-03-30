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
        Schema::create('customer_order_descriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_order_id');
            $table->string('item_name');
            $table->string('variation_name')->nullable();
            $table->text('note')->nullable();
            $table->integer('quantity')->nullable(); // Ensure quantity is nullable
            $table->decimal('price', 10, 2)->nullable(); // Ensure price is nullable
            $table->decimal('total', 10, 2)->nullable(); // Ensure total is nullable
            $table->timestamps();
            $table->boolean('is_variation')->default(0); 
        
            $table->foreign('customer_order_id')->references('order_id')->on('customer_orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_order_descriptions');
    }
};