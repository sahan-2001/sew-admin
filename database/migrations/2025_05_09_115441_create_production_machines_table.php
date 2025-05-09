<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_machines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('purchased_date');
            $table->date('start_working_date');
            $table->integer('expected_lifetime');
            $table->decimal('purchased_cost', 15, 2);
            $table->decimal('additional_cost', 15, 2)->nullable();
            $table->text('additional_cost_description')->nullable();
            $table->decimal('total_initial_cost', 15, 2);
            $table->decimal('depreciation_rate', 5, 2);
            $table->enum('depreciation_calculated_from', ['purchased_date', 'start_working_date']);
            $table->date('last_depreciation_calculated_date')->nullable();
            $table->decimal('depreciation_last', 5, 2)->nullable();
            $table->decimal('cumulative_depreciation', 15, 2)->default(0);
            $table->decimal('net_present_value', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_machines');
    }
};
