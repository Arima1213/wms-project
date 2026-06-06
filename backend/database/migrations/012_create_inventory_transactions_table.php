<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->enum('type', ['GR', 'GI', 'TR', 'LT', 'SO', 'ADJ+', 'ADJ-', 'RS', 'RC']);
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rack_slot_id')->nullable()->constrained('rack_slots')->nullOnDelete();
            $table->string('batch_number', 50)->nullable();
            $table->decimal('quantity', 12, 3);
            $table->decimal('before_quantity', 12, 3)->default(0);
            $table->decimal('after_quantity', 12, 3)->default(0);
            $table->enum('direction', ['in', 'out', 'transfer'])->default('in');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference_type', 50)->nullable(); // Inbound, Outbound, Transfer
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['warehouse_id', 'created_at']);
            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
