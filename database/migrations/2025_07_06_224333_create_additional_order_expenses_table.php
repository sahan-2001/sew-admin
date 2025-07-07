<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('additional_order_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('order_type'); 
            $table->unsignedBigInteger('order_id');
            $table->decimal('amount', 12, 2);
            $table->string('description');
            $table->date('recorded_date');
            $table->text('remarks')->nullable();
            $table->string('status')->default('created'); 
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('additional_order_expenses');
    }
};
