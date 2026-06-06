<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("inventory_transactions", function (Blueprint $table) {
            $table->id();
            $table->string("transaction_number", 30)->unique();
            $table->enum("type", ["GR","GI","TR","LT","SO","ADJ+","ADJ-","RS","RC"])->default("GR");
            $table->foreignId("warehouse_id")->constrained()->cascadeOnDelete();
            $table->string("reference_type")->nullable();
            $table->unsignedBigInteger("reference_id")->nullable();
            $table->foreignId("user_id")->constrained()->cascadeOnDelete();
            $table->enum("status", ["draft","pending","approved","rejected","completed","cancelled"])->default("draft");
            $table->enum("priority", ["low","normal","high","urgent"])->default("normal");
            $table->text("notes")->nullable();
            $table->json("metadata")->nullable();
            $table->timestamp("approved_at")->nullable();
            $table->foreignId("approved_by")->nullable()->constrained("users")->nullOnDelete();
            $table->timestamp("completed_at")->nullable();
            $table->timestamps();
            $table->index(["warehouse_id", "type", "status"]);
            $table->index("transaction_number");
            $table->index("created_at");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("inventory_transactions");
    }
};
