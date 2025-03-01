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
        Schema::create('sample_orders', function (Blueprint $table) {
            $table->id('order_id'); // Primary key
            $table->string('name'); // Order name
            $table->date('wanted_delivery_date'); // Delivery date
            $table->unsignedBigInteger('customer_id'); // Foreign key for customer
            $table->text('special_notes')->nullable(); // Notes for the order
            $table->string('status')->default('planned'); // Order status
            $table->unsignedBigInteger('added_by'); // Tracks the user who created the order
            $table->timestamps(); // Created_at and updated_at timestamps
            $table->softDeletes(); // Enable soft deletes

            // Foreign key constraints
            $table->foreign('customer_id')
                  ->references('customer_id')
                  ->on('customers')
                  ->onDelete('cascade'); // Deletes related orders if the customer is deleted

            $table->foreign('added_by')
                  ->references('id')
                  ->on('users') // Assumes the 'users' table is default
                  ->onDelete('cascade'); // Deletes the order if the user is deleted (adjust as necessary)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sample_orders');
    }
};
