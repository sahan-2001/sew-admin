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
        Schema::create('customer_control_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers', 'customer_id')->cascadeOnDelete();

            $table->foreignId('receivable_account_id')->nullable()->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->foreignId('sales_account_id')->nullable()->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->foreignId('vat_output_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('bad_debt_expense_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();

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
        Schema::dropIfExists('customer_control_accounts');
    }
};