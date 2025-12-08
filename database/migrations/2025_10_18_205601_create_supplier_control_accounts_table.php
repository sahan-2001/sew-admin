<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_control_accounts', function (Blueprint $table) {
            $table->id();

            // Supplier link
            $table->foreignId('supplier_id')
                ->constrained('suppliers', 'supplier_id')
                ->cascadeOnDelete();

            // Core Payables
            // $table->foreignId('payable_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            // Self account for payables

            $table->foreignId('supplier_advance_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            // Purchase Related Accounts
            $table->foreignId('purchase_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            $table->foreignId('purchase_return_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            $table->foreignId('purchase_discount_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            $table->foreignId('freight_in_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            $table->foreignId('grni_account_id')->nullable() // Goods Received Not Invoiced
                ->constrained('chart_of_accounts')->nullOnDelete()
                ->name('supplier_grni_fk');

            // VAT / GST / Tax Accounts
            $table->foreignId('vat_input_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            $table->foreignId('vat_suspense_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            // Manufacturing Specific Accounts
            // Direct Material Purchase
            $table->foreignId('direct_material_purchase_account_id')->nullable();
            $table->foreign('direct_material_purchase_account_id', 'sca_dm_purchase_fk')
                ->references('id')
                ->on('chart_of_accounts')
                ->nullOnDelete();

            // Indirect Material Purchase
            $table->foreignId('indirect_material_purchase_account_id')->nullable();
            $table->foreign('indirect_material_purchase_account_id', 'sca_im_purchase_fk')
                ->references('id')
                ->on('chart_of_accounts')
                ->nullOnDelete();

            // Production Supplies
            $table->foreignId('production_supplies_account_id')->nullable();
            $table->foreign('production_supplies_account_id', 'sca_prod_supplies_fk')
                ->references('id')
                ->on('chart_of_accounts')
                ->nullOnDelete();

            // Subcontracting Expense
            $table->foreignId('subcontracting_expense_account_id')->nullable();
            $table->foreign('subcontracting_expense_account_id', 'sca_subcontract_fk')
                ->references('id')
                ->on('chart_of_accounts')
                ->nullOnDelete();



            // Adjustments / Write-offs
            $table->foreignId('bad_debt_recovery_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            $table->foreignId('supplier_writeoff_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            $table->foreignId('purchase_price_variance_account_id')->nullable();
            $table->foreign('purchase_price_variance_account_id', 'sca_ppv_acc_fk')
                ->references('id')
                ->on('chart_of_accounts')
                ->nullOnDelete();

            // Totals
            $table->decimal('debit_total', 25, 2)->default(0.00);
            $table->decimal('credit_total', 25, 2)->default(0.00);
            $table->decimal('balance', 25, 2)->default(0.00);

            $table->decimal('debit_total_vat', 25, 2)->default(0.00);
            $table->decimal('credit_total_vat', 25, 2)->default(0.00);
            $table->decimal('balance_vat', 25, 2)->default(0.00);

            $table->string('status')->default('active');

            $table->foreignId('created_by');
            $table->foreignId('updated_by');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_control_accounts');
    }
};
