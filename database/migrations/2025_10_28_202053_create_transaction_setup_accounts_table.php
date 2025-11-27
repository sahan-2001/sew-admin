<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionSetupAccountsTable extends Migration
{
    public function up()
    {
        Schema::create('transaction_setup_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('transaction_setup_id');
            $table->foreign('transaction_setup_id')
                  ->references('id')->on('transaction_setups')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('debit_account_id_1')->nullable();
            $table->unsignedBigInteger('debit_account_id_2')->nullable();
            $table->unsignedBigInteger('debit_account_id_3')->nullable();
            $table->unsignedBigInteger('debit_account_id_4')->nullable();
            $table->unsignedBigInteger('debit_account_id_5')->nullable();

            $table->unsignedBigInteger('credit_account_id_1')->nullable();
            $table->unsignedBigInteger('credit_account_id_2')->nullable();
            $table->unsignedBigInteger('credit_account_id_3')->nullable();
            $table->unsignedBigInteger('credit_account_id_4')->nullable();
            $table->unsignedBigInteger('credit_account_id_5')->nullable();

            $table->string('status')->default('created'); 
            $table->foreignId('created_by');
            $table->foreignId('updated_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_setup_accounts');
    }
}
