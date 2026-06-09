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
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 10);       // INB, OUT, TRF, SO
            $table->date('date');                // per-hari counter
            $table->unsignedInteger('counter');  // nomor urut
            $table->timestamps();

            // Unique per prefix per day — otomatis reset counter per hari
            $table->unique(['prefix', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
