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
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('account_type', ['asset', 'equity', 'liability', 'income', 'expense']);
            $table->boolean('is_control_account')->default(false);
            $table->string('control_account_type')->nullable();
            $table->enum('sub_category', ['current', 'non_current'])->nullable();
            $table->enum('statement_type', ['balance_sheet', 'income_statement'])->default('balance_sheet');
            $table->text('description')->nullable();
            
            $table->foreignId('vat_output_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete()->nullable();
            $table->foreignId('vat_input_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();

            $table->decimal('debit_total', 25, 2)->default(0.00);
            $table->decimal('credit_total', 25, 2)->default(0.00);
            $table->decimal('balance', 25, 2)->default(0.00);
            
            $table->decimal('debit_total_vat', 25, 2)->default(0.00);
            $table->decimal('credit_total_vat', 25, 2)->default(0.00);
            $table->decimal('balance_vat', 25, 2)->default(0.00);
            
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
        Schema::dropIfExists('chart_of_accounts');
    }
};
