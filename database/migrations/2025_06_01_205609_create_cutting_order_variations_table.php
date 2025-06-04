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
        Schema::create('cutting_order_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cutting_record_id')->constrained();
            $table->foreignId('order_item_id')->constrained('cutting_order_items');
            $table->string('variation_type'); // 'variation_item' or 'sample_order_variation'
            $table->unsignedBigInteger('variation_id');
            $table->integer('quantity');
            $table->string('start_label')->nullable();
            $table->string('end_label')->nullable();
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
        Schema::dropIfExists('cutting_order_variations');
    }
};
