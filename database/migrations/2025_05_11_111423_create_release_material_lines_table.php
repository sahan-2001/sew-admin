<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReleaseMaterialLinesTable extends Migration
{
    public function up()
    {
        Schema::create('release_material_lines', function (Blueprint $table) {
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->id();
            $table->unsignedBigInteger('release_material_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('stock_id');
            $table->unsignedBigInteger('location_id');
            $table->integer('quantity');
            $table->decimal('cut_quantity', 10, 2)->default(0);
            $table->decimal('cost', 10, 2);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('release_material_id')->references('id')->on('release_materials')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('release_material_lines');
    }
}