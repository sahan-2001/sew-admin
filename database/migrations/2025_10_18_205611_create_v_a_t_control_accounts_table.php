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
        Schema::create('v_a_t_control_accounts', function (Blueprint $table) {
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->id();

            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
                        
            $table->enum('vat_account_type', ['purchase', 'sales'])
                ->comment('purchase = Input VAT, sales = Output VAT');
            $table->decimal('debit_total_vat', 25, 2)->default(0.00);
            $table->decimal('credit_total_vat', 25, 2)->default(0.00);
            $table->decimal('balance_vat', 25, 2)->default(0.00);
            
            $table->string('status')->default('created'); 
            $table->foreignId('created_by');
            $table->foreignId('updated_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('v_a_t_control_accounts');
    }
};
