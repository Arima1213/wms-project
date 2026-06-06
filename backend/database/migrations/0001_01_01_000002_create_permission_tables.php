<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names', [
            'roles' => 'roles',
            'permissions' => 'permissions',
            'model_has_permissions' => 'model_has_permissions',
            'model_has_roles' => 'model_has_roles',
            'role_has_permissions' => 'role_has_permissions',
        ]);

        Schema::create($tableNames['permissions'], function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->string('group')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['roles'], function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames) {
            $table->foreignId('permission_id')->constrained($tableNames['permissions'])->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames) {
            $table->foreignId('role_id')->constrained($tableNames['roles'])->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames) {
            $table->foreignId('permission_id')->constrained($tableNames['permissions'])->cascadeOnDelete();
            $table->foreignId('role_id')->constrained($tableNames['roles'])->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        app('cache')->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names', [
            'roles' => 'roles',
            'permissions' => 'permissions',
            'model_has_permissions' => 'model_has_permissions',
            'model_has_roles' => 'model_has_roles',
            'role_has_permissions' => 'role_has_permissions',
        ]);

        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }
};
