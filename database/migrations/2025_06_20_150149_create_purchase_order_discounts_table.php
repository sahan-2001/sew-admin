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
        Schema::create('purchase_order_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('unit_rate', 12, 2);
            $table->decimal('quantity', 12, 2);
            $table->string('uom');
            $table->decimal('total', 12, 2);
            $table->date('date');
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
        Schema::dropIfExists('purchase_order_discounts');
    }
};
