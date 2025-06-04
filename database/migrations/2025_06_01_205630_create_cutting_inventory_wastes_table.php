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
        Schema::create('cutting_inventory_waste', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cutting_record_id')->constrained();
            $table->foreignId('item_id')->constrained('inventory_items');
            $table->decimal('amount', 10, 2);
            $table->string('unit');
            $table->foreignId('location_id')->constrained('inventory_locations');
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
        Schema::dropIfExists('cutting_inventory_wastes');
    }
};
