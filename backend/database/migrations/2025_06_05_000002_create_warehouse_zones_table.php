<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("warehouse_zones", function (Blueprint $table) {
            $table->id();
            $table->foreignId("warehouse_id")->constrained()->cascadeOnDelete();
            $table->string("code", 10);
            $table->string("name");
            $table->string("color", 7)->default("#3B82F6");
            $table->decimal("min_temperature", 5, 2)->nullable();
            $table->decimal("max_temperature", 5, 2)->nullable();
            $table->integer("min_humidity")->nullable();
            $table->integer("max_humidity")->nullable();
            $table->json("allowed_categories")->nullable();
            $table->integer("sort_order")->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("warehouse_zones");
    }
};
