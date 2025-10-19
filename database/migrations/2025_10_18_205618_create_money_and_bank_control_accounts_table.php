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
        Schema::create('money_and_bank_control_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
                        
            $table->decimal('debit_total', 25, 2)->default(0.00);
            $table->decimal('credit_total', 25, 2)->default(0.00);
            $table->decimal('balance', 25, 2)->default(0.00);
            
            $table->string('status')->default('created'); 
            $table->foreignId('created_by');
            $table->foreignId('updated_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('money_and_bank_control_accounts');
    }
};
