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
        Schema::create('register_arrival_item_descriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('register_arrival_item_id');
            $table->string('item_code');
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->enum('status', ['to be inspected', 'approved', 'return', 'scrap']);
            $table->timestamps();

            $table->foreign('register_arrival_item_id', 'fk_register_arrival_item_id')
                  ->references('id')->on('register_arrival_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('register_arrival_item_descriptions', function (Blueprint $table) {
            $table->dropForeign('fk_register_arrival_item_id');
        });
        Schema::dropIfExists('register_arrival_item_descriptions');
    }
};