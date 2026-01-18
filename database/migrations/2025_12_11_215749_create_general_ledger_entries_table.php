<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeneralLedgerEntriesTable extends Migration
{
    public function up()
    {
        Schema::create('general_ledger_entries', function (Blueprint $table) {
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->id();
            $table->string('entry_code')->nullable();

            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('source_table')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->date('entry_date');
            $table->string('reference_table')->nullable();
            $table->unsignedBigInteger('reference_record_id')->nullable();
            
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->text('transaction_name')->nullable();
            $table->text('description')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('chart_of_accounts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['source_table', 'source_id']);
        });
    }

    public function down()
    {
        Schema::table('general_ledger_entries', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::dropIfExists('general_ledger_entries');
    }
}
