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
        Schema::create('cutting_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cutting_record_id')->constrained();
            $table->enum('order_type', ['customer_order', 'sample_order']);
            $table->string('order_id');
            $table->foreignId('order_item_id')->constrained('cutting_order_items');
            $table->foreignId('order_variation_id')->nullable()->constrained('cutting_order_variations');
            $table->integer('quantity');
            $table->string('label');
            $table->string('status');
            $table->string('barcode');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cutting_labels');
    }
};
