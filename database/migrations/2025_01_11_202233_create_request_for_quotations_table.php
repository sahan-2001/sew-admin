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
        Schema::create('request_for_quotations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->unsignedBigInteger('delivery_term_id')->nullable();
            $table->unsignedBigInteger('delivery_method_id')->nullable();
            $table->unsignedBigInteger('currency_code_id')->nullable();
            $table->date('wanted_delivery_date')->nullable();
            $table->date('valid_until')->nullable();
            $table->text('special_note')->nullable();

            $table->string('status')->default('draft');

            $table->string('random_code')->nullable();

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_for_quotations');
    }
};
