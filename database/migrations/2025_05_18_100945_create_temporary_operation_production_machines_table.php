<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temporary_operation_production_machines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('temporary_operation_id')->constrained()->onDelete('cascade')->index('temp_op_machine_temp_op_id_foreign');;
            $table->foreignId('production_machine_id')->constrained()->onDelete('cascade')->index('temp_op_machine_machine_id_foreign');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_operation_production_machines');
    }
};
