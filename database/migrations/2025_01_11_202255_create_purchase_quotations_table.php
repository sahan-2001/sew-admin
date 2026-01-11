<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_quotations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->foreignId('request_for_quotation_id')->nullable();
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->unsignedBigInteger('delivery_term_id')->nullable();
            $table->unsignedBigInteger('delivery_method_id')->nullable();
            $table->unsignedBigInteger('currency_code_id')->nullable();
            
            $table->date('wanted_delivery_date')->nullable();
            $table->date('promised_delivery_date')->nullable();
            $table->date('quotation_date')->nullable();
            $table->date('valid_until')->nullable();
            $table->text('special_note')->nullable();
            $table->string('status')->default('planned'); 
            $table->unsignedBigInteger('supplier_vat_group_id')->nullable();
            $table->decimal('supplier_vat_rate', 5, 2)->default(0)->nullable();
            $table->decimal('order_subtotal', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->text('vat_base')->nullable();
            $table->decimal('remaining_balance', 12, 2)->default(0);

            $table->string('supplier_quotation_number')->nullable();
            $table->date('received_date')->nullable();
            $table->date('estimated_delivery_date')->nullable();
            $table->text('supplier_note')->nullable();
            $table->string('image_of_quotation')->nullable(); 

            $table->string('random_code')->nullable(); 
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes(); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_quotations');
    }
};
