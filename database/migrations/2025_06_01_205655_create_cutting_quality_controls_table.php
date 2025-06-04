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
        Schema::create('cutting_quality_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cutting_record_id')->constrained();
            $table->foreignId('qc_user_id')->constrained('users');
            $table->integer('inspected_quantity');
            $table->integer('accepted_quantity');
            $table->integer('rejected_quantity')->virtualAs('inspected_quantity - accepted_quantity');
            $table->text('notes')->nullable();
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
        Schema::dropIfExists('cutting_quality_controls');
    }
};
