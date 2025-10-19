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
        Schema::create('supplier_control_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_id')->constrained('suppliers', 'supplier_id')->cascadeOnDelete();

            $table->foreignId('payable_account_id')->nullable()->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->foreignId('purchase_account_id')->nullable()->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->foreignId('vat_input_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('purchase_discount_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('bad_debt_recovery_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();

            $table->decimal('debit_total', 25, 2)->default(0.00);
            $table->decimal('credit_total', 25, 2)->default(0.00);
            $table->decimal('balance', 25, 2)->default(0.00);

            $table->decimal('debit_total_vat', 25, 2)->default(0.00);
            $table->decimal('credit_total_vat', 25, 2)->default(0.00);
            $table->decimal('balance_vat', 25, 2)->default(0.00);

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
        Schema::dropIfExists('supplier_control_accounts');
    }
};