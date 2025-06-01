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
        Schema::create('cut_piece_labels', function (Blueprint $table) {
            $table->id();
            $table->string('order_type');
            $table->unsignedBigInteger('order_id');
            $table->foreignId('cutting_record_id')->constrained()->onDelete('cascade');
            $table->integer('number_of_pieces');
            $table->unsignedBigInteger('label_start');
            $table->unsignedBigInteger('label_end'); // label_end = label_start + number_of_pieces - 1
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
        });

        // Add a unique index to the combination of cutting_record_id, order_type, and order_id
        Schema::table('cut_piece_labels', function (Blueprint $table) {
            $table->unique(['cutting_record_id', 'order_type', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cut_piece_labels');
    }
};
