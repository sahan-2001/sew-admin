<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionSetupsTable extends Migration
{
    public function up()
    {
        Schema::create('transaction_setups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('transaction_name', 191);
            $table->text('description')->nullable();
            $table->text('remarks')->nullable();
            
            $table->string('status')->default('created'); 
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_setups');
    }
}
