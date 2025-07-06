<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerAdvanceInvoicesTable extends Migration
{
    public function up()
    {
        Schema::create('customer_advance_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('order_type'); 
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('customer_id');
            $table->decimal('grand_total', 10, 2);
            $table->decimal('amount', 10, 2)->nullable();
            $table->date('paid_date')->nullable();
            $table->string('paid_via')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('cus_invoice_number')->nullable();
            $table->string('invoice_image')->nullable();
            $table->string('status')->default('received'); 

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('supplier_advance_invoices');
    }
}