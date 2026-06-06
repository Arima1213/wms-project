<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("stock_items", function (Blueprint $table) {
            $table->id();
            $table->foreignId("slot_id")->constrained("rack_slots")->cascadeOnDelete();
            $table->foreignId("product_id")->constrained()->cascadeOnDelete();
            $table->string("batch_number", 50)->nullable();
            $table->date("manufacture_date")->nullable();
            $table->date("expiry_date")->nullable();
            $table->decimal("quantity", 12, 4)->default(0);
            $table->decimal("reserved_quantity", 12, 4)->default(0);
            $table->decimal("avg_cost_price", 12, 2)->default(0);
            $table->string("lot_number", 50)->nullable();
            $table->text("notes")->nullable();
            $table->timestamps();
            $table->index(["slot_id", "product_id"]);
            $table->index("batch_number");
            $table->index("expiry_date");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("stock_items");
    }
};
