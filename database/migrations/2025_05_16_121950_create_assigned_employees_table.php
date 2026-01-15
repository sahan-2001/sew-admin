<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assigned_employees', function (Blueprint $table) {
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('assign_daily_operation_line_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('assign_daily_operation_line_id')->references('id')->on('assign_daily_operation_lines')->onDelete('cascade');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('assigned_employees');
    }
};