<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("product_barcodes", function (Blueprint $table) {
            $table->id();
            $table->foreignId("product_id")->constrained()->cascadeOnDelete();
            $table->string("barcode", 50);
            $table->enum("type", ["ean", "upc", "internal", "qr"])->default("internal");
            $table->boolean("is_primary")->default(false);
            $table->timestamps();
            $table->unique(["barcode", "type"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("product_barcodes");
    }
};
