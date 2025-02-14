<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('customer_order_descriptions', function (Blueprint $table) {
            $table->boolean('is_variation')->default(0); 
            $table->integer('quantity')->nullable()->change();
            $table->decimal('price', 10, 2)->nullable()->change();
            $table->decimal('total', 10, 2)->nullable()->change();
        });

    }

    public function down()
    {
        Schema::table('customer_order_descriptions', function (Blueprint $table) {
            $table->dropColumn('is_variation');
            $table->integer('quantity')->nullable(false)->change();
            $table->decimal('price', 10, 2)->nullable(false)->change();
            $table->decimal('total', 10, 2)->nullable(false)->change();
        });
    }

};



