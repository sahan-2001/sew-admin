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
        Schema::create('fixed_asset_control_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('asset_category'); // e.g., Buildings, Machinery, Vehicles

            $table->decimal('purchasing_cost', 15, 2)->default(0);
            $table->decimal('additional_cost_1', 15, 2)->default(0);
            $table->string('additional_cost_description_1')->nullable();

            $table->decimal('additional_cost_2', 15, 2)->default(0);
            $table->string('additional_cost_description_2')->nullable();

            $table->decimal('additional_cost_3', 15, 2)->default(0);
            $table->string('additional_cost_description_3')->nullable();
            $table->decimal('total_initial_cost', 15, 2)->default(0);

            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->decimal('net_book_value', 15, 2)->default(0);
            
            $table->decimal('debit_balance', 15, 2)->default(0);
            $table->decimal('credit_balance', 15, 2)->default(0);
            $table->decimal('net_debit_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            
            $table->string('status')->default('created'); 
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_asset_control_accounts');
    }
};
