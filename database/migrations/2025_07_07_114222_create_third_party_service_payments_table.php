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
        Schema::create('third_party_service_payments', function (Blueprint $table) {
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->id();
            $table->foreignId('third_party_service_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('supplier_id');
            $table->decimal('remaining_balance', 12, 2);
            $table->decimal('payable_balance_old', 12, 2);
            $table->decimal('paid_amount', 12, 2);
            $table->enum('paid_via', ['cash', 'card', 'cheque']);
            $table->string('reference')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('third_party_service_payments');
    }
};
