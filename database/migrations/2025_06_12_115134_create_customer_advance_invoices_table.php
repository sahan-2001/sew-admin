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
            $table->date('wanted_date')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_advance_invoices');
    }
}