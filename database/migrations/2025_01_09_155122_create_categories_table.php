<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesTable extends Migration
{
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->foreignId('site_id')->constrained()->onDelete('cascade')->nullable();
            $table->id();
            $table->string('name')->unique();
            $table->unsignedBigInteger('created_by')->nullable(); // Add created_by field
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null'); 
            $table->unsignedBigInteger('updated_by')->nullable(); 
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['created_by']); // Drop foreign key constraint
        });
        Schema::dropIfExists('categories');
    }
}