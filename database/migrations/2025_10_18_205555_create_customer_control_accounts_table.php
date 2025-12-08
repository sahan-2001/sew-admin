<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_control_accounts', function (Blueprint $table) {
            $table->id();

            // Link to Customer
            $table->foreignId('customer_id')
                ->constrained('customers', 'customer_id')
                ->cascadeOnDelete();

            // Customer Related Accounts -----------------------------
            // $table->foreignId('receivable_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            // Self account for receivables

            $table->foreignId('sales_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            $table->foreignId('export_sales_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            $table->foreignId('sales_return_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            $table->foreignId('sales_discount_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            $table->foreignId('customer_advance_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            $table->foreignId('bad_debt_expense_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            $table->foreignId('allowance_for_doubtful_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete()
                ->name('cust_ctrl_allow_doubt_fk');

            $table->foreignId('vat_output_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            $table->foreignId('vat_receivable_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            // Cash/Bank Accounts -----------------------------------
            $table->foreignId('cash_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            $table->foreignId('bank_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            $table->foreignId('undeposited_funds_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete()
                ->name('cust_ctrl_undep_funds_fk');

            // COGS / Inventory -------------------------------------
            $table->foreignId('cogs_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            $table->foreignId('inventory_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            // Summary fields ----------------------------------------
            $table->decimal('debit_total', 25, 2)->default(0);
            $table->decimal('credit_total', 25, 2)->default(0);
            $table->decimal('balance', 25, 2)->default(0);

            $table->decimal('debit_total_vat', 25, 2)->default(0);
            $table->decimal('credit_total_vat', 25, 2)->default(0);
            $table->decimal('balance_vat', 25, 2)->default(0);

            $table->string('status')->default('active');

            $table->foreignId('created_by');
            $table->foreignId('updated_by');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_control_accounts');
    }
};
