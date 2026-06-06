<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("transaction_items", function (Blueprint $table) {
            $table->id();
            $table->foreignId("transaction_id")->constrained()->cascadeOnDelete();
            $table->foreignId("product_id")->constrained()->cascadeOnDelete();
            $table->foreignId("from_slot_id")->nullable()->constrained("rack_slots")->nullOnDelete();
            $table->foreignId("to_slot_id")->nullable()->constrained("rack_slots")->nullOnDelete();
            $table->string("batch_number", 50)->nullable();
            $table->date("expiry_date")->nullable();
            $table->decimal("quantity", 12, 4)->default(0);
            $table->decimal("uom_quantity", 12, 4)->default(0);
            $table->string("uom")->default("pcs");
            $table->decimal("cost_price", 12, 2)->default(0);
            $table->string("lot_number", 50)->nullable();
            $table->text("notes")->nullable();
            $table->timestamps();
            $table->index(["transaction_id", "product_id"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("transaction_items");
    }
};
