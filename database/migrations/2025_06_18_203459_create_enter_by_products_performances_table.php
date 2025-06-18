<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnterByProductsPerformancesTable extends Migration
{
    public function up()
    {
        Schema::create('enter_by_products_performances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enter_performance_record_id');
            $table->decimal('amount', 10, 2);
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('location_id');
            $table->string('uom', 50);
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('enter_by_products_performances');
    }
}