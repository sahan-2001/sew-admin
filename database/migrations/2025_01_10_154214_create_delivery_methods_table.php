<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Courier", "Air Freight", "Sea Freight"
            $table->text('description')->nullable(); // optional details
            
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_methods');
    }
};
