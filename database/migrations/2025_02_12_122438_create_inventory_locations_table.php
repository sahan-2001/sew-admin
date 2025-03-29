<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryLocationsTable extends Migration
{
    public function up()
    {
        Schema::create('inventory_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('note')->nullable();
            $table->decimal('capacity', 10, 2);
            $table->enum('measurement_unit', ['liters', 'm^3', 'cm^3', 'box', 'pallets', 'other']);
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
            $table->enum('location_type', ['arrival', 'picking', 'shipment']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_locations');
    }
}
