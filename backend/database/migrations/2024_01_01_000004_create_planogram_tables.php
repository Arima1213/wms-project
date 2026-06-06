<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Planograms
        Schema::create('planograms', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('warehouse_id');
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->text('description')->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->string('grid_size', 10)->default('10px');
            $table->json('background')->nullable();
            $table->json('elements'); // Array of planogram elements
            $table->boolean('is_published')->default(false);
            $table->string('version', 10)->default('1.0');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->index('code');
            $table->index('warehouse_id');
        });

        // Webhooks
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('url');
            $table->string('event', 50);
            $table->string('method', 10)->default('POST');
            $table->json('headers')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('secret')->nullable();
            $table->integer('retry_count')->default(3);
            $table->integer('timeout')->default(30);
            $table->timestamps();
            $table->index('event');
            $table->index('is_active');
        });

        // Webhook Logs
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('webhook_id');
            $table->string('event', 50);
            $table->string('status', 20)->default('pending');
            $table->integer('response_code')->nullable();
            $table->text('request_payload')->nullable();
            $table->text('response_body')->nullable();
            $table->string('error_message')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->foreign('webhook_id')->references('id')->on('webhooks')->onDelete('cascade');
            $table->index('webhook_id');
            $table->index('status');
        });

        // Activity Logs
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('user_id');
            $table->string('action', 50);
            $table->string('model_type', 100)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });

        // Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('user_id');
            $table->string('type', 100);
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('is_read');
        });

        // Settings
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->string('group', 50)->default('general');
            $table->timestamps();
            $table->index('key');
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('planograms');
    }
};