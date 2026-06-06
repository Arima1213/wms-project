<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("planograms", function (Blueprint $table) {
            $table->id();
            $table->foreignId("warehouse_id")->constrained()->cascadeOnDelete();
            $table->string("name");
            $table->text("description")->nullable();
            $table->json("canvas_data");
            $table->json("metadata")->nullable();
            $table->bigInteger("version")->default(1);
            $table->foreignId("created_by")->constrained()->cascadeOnDelete();
            $table->foreignId("approved_by")->nullable()->constrained("users")->nullOnDelete();
            $table->timestamp("approved_at")->nullable();
            $table->enum("status", ["draft","active","archived"])->default("draft");
            $table->timestamps();
            $table->index(["warehouse_id", "status"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("planograms");
    }
};
