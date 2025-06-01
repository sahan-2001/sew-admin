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
        Schema::create('cutting_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cutting_station_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->time('time_from');
            $table->time('time_to');
            $table->string('order_type');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('release_material_line_id')->nullable();

            $table->decimal('waste')->nullable();
            $table->unsignedBigInteger('waste_item_id')->nullable();
            $table->unsignedBigInteger('waste_item_location_id')->nullable();

            $table->decimal('by_product_amount')->nullable();
            $table->unsignedBigInteger('by_product_id')->nullable();
            $table->unsignedBigInteger('by_product_location_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
        });
        // Add a unique index to the combination of cutting_station_id, date, time_from, and time_to
        Schema::table('cutting_records', function (Blueprint $table) {
            $table->unique(['cutting_station_id', 'date', 'time_from', 'time_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cutting_records');
    }
};
