<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('supplier_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('shop_name');
            $table->string('address');
            $table->string('email')->unique();
            $table->string('phone_1');
            $table->string('phone_2')->nullable();
            $table->decimal('outstanding_balance', 10, 2)->default(0);
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->text('note')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('supplier_requests');
    }
}