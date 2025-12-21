<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_advance_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliers', 'supplier_id')->onDelete('cascade');
            $table->string('status')->default('pending');
            
            // Payment details
            $table->string('payment_type')->nullable(); // fixed, percentage
            $table->decimal('fix_payment_amount', 15, 2)->nullable();
            $table->decimal('payment_percentage', 5, 2)->nullable();
            $table->decimal('percent_calculated_payment', 15, 2)->nullable();
            
            // Amount fields
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            
            // Payment info
            $table->date('paid_date')->nullable();
            $table->string('paid_via')->nullable();
            
            // Account references
            $table->foreignId('supplier_control_account_id')->nullable();
            $table->foreignId('supplier_advance_account_id')->nullable();
            
            // Random code
            $table->string('random_code', 16)->unique();
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('purchase_order_id');
            $table->index('supplier_id'); // Changed from provider_id
            $table->index('status');
            $table->index('random_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_advance_invoices');
    }
};