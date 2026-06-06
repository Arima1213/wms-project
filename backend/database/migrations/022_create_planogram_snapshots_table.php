<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planogram_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planogram_id')->constrained()->cascadeOnDelete();
            $table->string('version', 20);
            $table->json('canvas_data');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->text('change_summary')->nullable();
            $table->timestamp('created_at');
 });
    }

    public function down(): void
    {
        Schema::dropIfExists('planogram_snapshots');
    }
};
