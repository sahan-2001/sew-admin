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
        Schema::create('po_adv_inv_deduct', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('advance_invoice_id')->constrained('supplier_advance_invoices')->cascadeOnDelete();
            $table->decimal('deduction_amount', 12, 2);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('po_adv_inv_deduct');
    }
};
