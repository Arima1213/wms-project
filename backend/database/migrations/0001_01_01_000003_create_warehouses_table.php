<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('capacity_m2', 12, 2)->nullable();
            $table->string('pic_name')->nullable();
            $table->string('pic_phone', 20)->nullable();
            $table->string('pic_email')->nullable();
            $table->json('operating_hours')->nullable();
            $table->enum('warehouse_type', ['reguler', 'cold_storage', 'bonded', 'konsinyasi'])->default('reguler');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active', 'warehouse_type']);
        });

        // Add FK on users after warehouses exist
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('default_warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['default_warehouse_id']);
        });
        Schema::dropIfExists('warehouses');
    }
};
