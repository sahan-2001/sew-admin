<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAcceptedByAndConfirmationMessageToSampleOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('sample_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('accepted_by')->nullable();
            $table->text('confirmation_message')->nullable();
        });
    }

    public function down()
    {
        Schema::table('sample_orders', function (Blueprint $table) {
            $table->dropColumn(['accepted_by', 'confirmation_message']);
        });
    }
}
