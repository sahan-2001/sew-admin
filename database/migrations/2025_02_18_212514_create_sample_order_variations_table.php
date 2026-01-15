<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSampleOrderVariationsTable extends Migration
{
    public function up()
    {
        Schema::create('sample_order_variations', function (Blueprint $table) {
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->id(); 
            $table->foreignId('sample_order_item_id')->constrained()->onDelete('cascade'); 
            $table->string('variation_name'); 
            $table->integer('quantity'); 
            $table->decimal('price', 10, 2); 
            $table->decimal('total', 10, 2); 
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps(); 
        });
    }

    public function down()
    {
        Schema::dropIfExists('sample_order_variations');
    }
}
