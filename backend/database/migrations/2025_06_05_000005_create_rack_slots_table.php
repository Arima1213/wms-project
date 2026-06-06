<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("rack_slots", function (Blueprint $table) {
            $table->id();
            $table->foreignId("rack_level_id")->constrained()->cascadeOnDelete();
            $table->smallInteger("slot_number");
            $table->string("slot_code", 30);
            $table->decimal("max_weight_kg", 8, 2)->default(100);
            $table->decimal("max_height_cm", 6, 2)->default(50);
            $table->decimal("max_width_cm", 6, 2)->default(60);
            $table->decimal("max_depth_cm", 6, 2)->default(60);
            $table->enum("slot_type", ["fixed", "floating"])->default("floating");
            $table->foreignId("fixed_product_id")->nullable()->constrained("products")->nullOnDelete();
            $table->enum("status", ["empty", "partial", "full", "reserved"])->default("empty");
            $table->timestamp("reserved_until")->nullable();
            $table->string("reserved_for")->nullable();
            $table->boolean("is_active")->default(true);
            $table->timestamps();
            $table->unique(["rack_level_id", "slot_number"]);
            $table->unique(["slot_code"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("rack_slots");
    }
};
