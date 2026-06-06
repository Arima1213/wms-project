<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uoms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('symbol', 10);
            $table->enum('type', ['unit', 'weight', 'volume', 'length'])->default('unit');
            $table->decimal('conversion_factor', 10, 4)->default(1.0000);
            $table->unsignedBigInteger('base_uom_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('base_uom_id')->references('id')->on('uoms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uoms');
    }
};
