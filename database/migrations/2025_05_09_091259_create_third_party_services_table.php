<?php

// database/migrations/xxxx_xx_xx_create_third_party_services_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThirdPartyServicesTable extends Migration
{
    public function up()
    {
        Schema::create('third_party_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->foreign('supplier_id')
                ->references('supplier_id') 
                ->on('suppliers')
                ->onDelete('cascade');
            $table->string('name');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('third_party_services');
    }
}
