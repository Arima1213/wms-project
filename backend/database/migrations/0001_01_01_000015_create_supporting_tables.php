<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 50);
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['entity_type', 'entity_id']);
            $table->index('user_id');
            $table->index('created_at');
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('documentable_type', 100)->nullable();
            $table->unsignedBigInteger('documentable_id')->nullable();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->string('disk', 20)->default('minio');
            $table->string('path');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['documentable_type', 'documentable_id']);
        });

        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('secret', 64);
            $table->json('events');
            $table->boolean('is_active')->default(true);
            $table->json('headers')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->json('payload');
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->json('response_body')->nullable();
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->index(['webhook_id', 'delivered_at']);
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('audit_logs');
    }
};
