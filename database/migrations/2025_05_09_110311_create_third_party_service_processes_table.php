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
        Schema::create('third_party_service_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('third_party_service_id')->constrained()->onDelete('cascade');
            $table->string('description');
            $table->string('related_table')->nullable();
            $table->unsignedBigInteger('related_record_id')->nullable();
            $table->string('unit_of_measurement');
            $table->decimal('amount', 10, 2);
            $table->decimal('unit_rate', 10, 2);
            $table->decimal('total', 10, 2)->storedAs('amount * unit_rate');
            $table->decimal('outstanding_balance', 10, 2)->storedAs('amount * unit_rate');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('third_party_service_processes');
    }
};
