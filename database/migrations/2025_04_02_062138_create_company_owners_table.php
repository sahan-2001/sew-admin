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
        // database/migrations/xxxx_xx_xx_create_company_owners_table.php
        Schema::create('company_owners', function (Blueprint $table) {
            $table->foreignId('site_id')->constrained()->onDelete('cascade')->nullable();
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('address_line_3')->nullable();
            $table->string('city');
            $table->string('postal_code');
            $table->string('country')->default('Sri Lanka');
            $table->string('phone_1');
            $table->string('email');
            $table->string('phone_2')->nullable();
            $table->date('joined_date');
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->after('joined_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_owners');
    }
};
