<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReleaseMaterialsTable extends Migration
{
    public function up()
    {
        Schema::create('release_materials', function (Blueprint $table) {
            $table->id();
            $table->string('order_type'); // 'customer_order' or 'sample_order'
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('cutting_station_id')->nullable();
            $table->text('notes')->nullable();
            $table->text('status')->default('released');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('release_materials');
    }
}