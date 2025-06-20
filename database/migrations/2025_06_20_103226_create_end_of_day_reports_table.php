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
        Schema::create('end_of_day_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->unique();
            $table->text('summary')->nullable();
            $table->integer('total_output')->default(0);
            $table->integer('total_waste')->default(0);
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('end_of_day_reports');
    }
};
