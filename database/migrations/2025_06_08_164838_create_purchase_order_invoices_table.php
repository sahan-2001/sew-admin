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
        Schema::create('purchase_order_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('register_arrival_id')->constrained()->onDelete('cascade');
            $table->string('provider_type');
            $table->unsignedBigInteger('provider_id');
            $table->string('status')->default('pending');
            $table->decimal('grand_total', 15, 2)->default(0.00);
            $table->decimal('adv_paid', 15, 2)->default(0.00);
            $table->decimal('additional_cost', 15, 2)->default(0.00);
            $table->decimal('discount', 15, 2)->default(0.00);
            $table->decimal('due_payment', 15, 2)->default(0.00);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('random_code')->nullable(); 

            $table->softDeletes();
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_invoices');
    }
};
