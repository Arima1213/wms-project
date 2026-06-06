<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("racks", function (Blueprint $table) {
            $table->id();
            $table->foreignId("zone_id")->constrained("warehouse_zones")->cascadeOnDelete();
            $table->string("code", 20);
            $table->string("name")->nullable();
            $table->decimal("pos_x", 8, 2)->default(0);
            $table->decimal("pos_y", 8, 2)->default(0);
            $table->decimal("width", 8, 2)->default(4);
            $table->decimal("depth", 8, 2)->default(2);
            $table->decimal("rotation", 5, 2)->default(0);
            $table->integer("levels")->default(3);
            $table->integer("columns_per_level")->default(4);
            $table->decimal("max_weight_per_kg", 8, 2)->default(500);
            $table->boolean("is_active")->default(true);
            $table->timestamps();
            $table->unique(["zone_id", "code"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("racks");
    }
};
