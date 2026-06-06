<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("rack_levels", function (Blueprint $table) {
            $table->id();
            $table->foreignId("rack_id")->constrained()->cascadeOnDelete();
            $table->tinyInteger("level_number");
            $table->decimal("height_cm", 6, 2)->default(200);
            $table->decimal("max_weight_kg", 8, 2)->default(200);
            $table->integer("sort_order")->default(0);
            $table->timestamps();
            $table->unique(["rack_id", "level_number"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("rack_levels");
    }
};
