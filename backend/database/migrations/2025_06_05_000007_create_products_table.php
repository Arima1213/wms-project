<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("products", function (Blueprint $table) {
            $table->id();
            $table->string("sku", 50)->unique();
            $table->string("barcode", 50)->nullable();
            $table->string("name");
            $table->text("description")->nullable();
            $table->foreignId("category_id")->nullable()->constrained("product_categories")->nullOnDelete();
            $table->decimal("length_cm", 6, 2)->default(0);
            $table->decimal("width_cm", 6, 2)->default(0);
            $table->decimal("height_cm", 6, 2)->default(0);
            $table->decimal("weight_kg", 8, 3)->default(0);
            $table->decimal("min_stock", 12, 2)->default(0);
            $table->decimal("max_stock", 12, 2)->default(0);
            $table->decimal("reorder_point", 12, 2)->default(0);
            $table->decimal("cost_price", 12, 2)->default(0);
            $table->decimal("selling_price", 12, 2)->default(0);
            $table->string("base_uom", 10)->default("pcs");
            $table->decimal("weight_per_pcs_kg", 8, 4)->default(0);
            $table->boolean("track_expiry")->default(false);
            $table->integer("shelf_life_days")->nullable();
            $table->string("image_url")->nullable();
            $table->json("documents")->nullable();
            $table->boolean("is_active")->default(true);
            $table->boolean("requires_cold_storage")->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(["category_id", "is_active"]);
            $table->index("sku");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("products");
    }
};
