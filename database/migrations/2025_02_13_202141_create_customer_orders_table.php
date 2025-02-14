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
        Schema::create('customer_orders', function (Blueprint $table) {
            $table->id('order_id');
            $table->string('name');
            $table->date('wanted_delivery_date');
            $table->unsignedBigInteger('customer_id');
            $table->text('special_notes')->nullable();
            $table->string('status')->default('planned');
            $table->unsignedBigInteger('added_by'); // Track the user who created the order
            $table->timestamps();
            $table->softDeletes(); // Add softDeletes column for soft deletes

            $table->foreign('customer_id')
                  ->references('customer_id')
                  ->on('customers')
                  ->onDelete('cascade');

            $table->foreign('added_by')
                  ->references('id')
                  ->on('users')  // Assuming you're using Laravel's default 'users' table
                  ->onDelete('cascade'); // If the user is deleted, the order will be removed (or you can change this behavior)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_orders');
    }
};
