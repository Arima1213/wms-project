<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("uom_conversions", function (Blueprint $table) {
            $table->id();
            $table->foreignId("product_id")->constrained()->cascadeOnDelete();
            $table->string("from_uom", 10);
            $table->string("to_uom", 10);
            $table->decimal("conversion_factor", 10, 4);
            $table->boolean("is_active")->default(true);
            $table->timestamps();
            $table->unique(["product_id", "from_uom", "to_uom"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("uom_conversions");
    }
};
