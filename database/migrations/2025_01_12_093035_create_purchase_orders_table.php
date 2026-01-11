<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->unsignedBigInteger('delivery_term_id')->nullable();
            $table->unsignedBigInteger('delivery_method_id')->nullable();
            $table->unsignedBigInteger('currency_code_id')->nullable();
            
            $table->date('wanted_delivery_date')->nullable();
            $table->date('promised_delivery_date')->nullable();
            $table->text('special_note')->nullable();
            $table->string('status')->default('planned'); 
            $table->unsignedBigInteger('supplier_vat_group_id')->nullable();
            $table->decimal('supplier_vat_rate', 5, 2)->default(0)->nullable();
            $table->decimal('order_subtotal', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->text('vat_base')->nullable();
            $table->decimal('remaining_balance', 12, 2)->default(0);
            $table->string('random_code')->nullable(); 
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_orders');
    }
}