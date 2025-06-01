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
        Schema::create('cutting_q_c_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cutting_record_id')->constrained()->onDelete('cascade');
            $table->foreignId('qc_user_id')->constrained('users')->onDelete('cascade'); // Quality control officer
            $table->integer('inspected_pieces')->default(0);
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cutting_q_c_records');
    }
};
