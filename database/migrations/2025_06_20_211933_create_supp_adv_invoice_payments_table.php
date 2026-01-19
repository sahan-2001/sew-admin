<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('supp_adv_invoice_payments', function (Blueprint $table) {
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->id();
            $table->foreignId('supplier_advance_invoice_id')->constrained()->onDelete('cascade');
            $table->decimal('payment_amount', 15, 2);
            $table->decimal('remaining_amount_before', 15, 2);
            $table->decimal('remaining_amount_after', 15, 2);
            $table->string('payment_method')->default('cash');
            $table->unsignedBigInteger('credit_account_id')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('paid_by')->constrained('users');
            $table->timestamp('paid_at');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('supplier_advance_invoice_payments');
    }
};