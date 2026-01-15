<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sample_orders', function (Blueprint $table) {
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->id('order_id'); 
            $table->string('name'); 
            $table->date('wanted_delivery_date');
            $table->unsignedBigInteger('customer_id'); 
            $table->text('special_notes')->nullable(); 
            $table->string('status')->default('planned'); 
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('remaining_balance', 12, 2)->default(0);
            $table->unsignedBigInteger('added_by');
            $table->unsignedBigInteger('accepted_by')->nullable();
            $table->text('confirmation_message')->nullable(); 
            $table->unsignedBigInteger('rejected_by')->nullable(); 
            $table->text('rejection_message')->nullable(); 
            $table->string('random_code')->nullable(); 
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps(); 
            $table->softDeletes(); 

            // Foreign keys
            $table->foreign('customer_id')
                  ->references('customer_id')
                  ->on('customers')
                  ->onDelete('cascade'); 

            $table->foreign('added_by')
                  ->references('id')
                  ->on('users') 
                  ->onDelete('cascade'); 
                  
            $table->foreign('accepted_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null'); 

            $table->foreign('rejected_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null'); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sample_orders');
    }
};
