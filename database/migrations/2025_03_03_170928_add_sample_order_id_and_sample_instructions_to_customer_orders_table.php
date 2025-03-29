<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSampleOrderIdAndSampleInstructionsToCustomerOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('sample_order_id')->nullable(); 
            $table->text('sample_instructions')->nullable();
            $table->foreign('sample_order_id')->references('order_id')->on('sample_orders')->onDelete('set null');
            
        });
    }

    public function down()
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->dropForeign(['sample_order_id']); 
            $table->dropColumn(['sample_order_id', 'sample_instructions']);
        });
    }
}

