<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('provider_type');
            $table->unsignedBigInteger('provider_id');
            $table->string('provider_name');
            $table->string('provider_email');
            $table->string('provider_phone');
            $table->date('wanted_date');
            $table->text('special_note')->nullable();
            $table->string('status')->default('planned'); 
            $table->string('random_code')->nullable(); 
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_orders');
    }
}