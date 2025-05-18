<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('u_m_operation_line_machines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_machine_id');
            $table->unsignedBigInteger('u_m_operation_line_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('u_m_operation_line_id', 'apm_adol_id_fk')
                ->references('id')
                ->on('u_m_operation_lines')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('u_m_operation_line_machines');
    }
};
