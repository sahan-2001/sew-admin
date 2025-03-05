<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSampleOrderIdAndSampleInstructionsToCustomerOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            // Adding 'sample_order_id' and 'sample_instructions' columns
            $table->unsignedBigInteger('sample_order_id')->nullable(); // Foreign key can be nullable
            $table->text('sample_instructions')->nullable(); // Instructions can be nullable

            // You can add a foreign key constraint if sample_order_id references sample_orders table
            $table->foreign('sample_order_id')->references('order_id')->on('sample_orders')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            // Drop the columns in the 'down' method for rollback
            $table->dropForeign(['sample_order_id']); // Drop foreign key first
            $table->dropColumn(['sample_order_id', 'sample_instructions']);
        });
    }
}

