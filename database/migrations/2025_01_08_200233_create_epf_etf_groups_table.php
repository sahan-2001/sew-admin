<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('epf_etf_groups', function (Blueprint $table) {
            $table->unsignedBigInteger('site_id');
            $table->id();
            $table->string('name'); // e.g. Permanent Staff, Contract Staff
            $table->decimal('epf_employee_percentage', 5, 2);
            $table->decimal('epf_employer_percentage', 5, 2);
            $table->decimal('etf_employer_percentage', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->text('remarks')->nullable();
            
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('epf_etf_groups');
    }
};
