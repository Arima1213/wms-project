<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planograms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('warehouse_id');
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('width')->default(1920); // pixel
            $table->integer('height')->default(1080);
            $table->json('canvas_data'); // Konva stage JSON
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->uuid('created_by');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planograms');
    }
};
