<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $teams = config('permission.teams', false);
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not found.');
        }

        if ($teams && empty($columnNames['team_foreign_key'] ?? null)) {
            throw new \Exception('Error: team_foreign_key on config/permission.php not found.');
        }

        // Roles table
        Schema::create($tableNames['roles'], function (Blueprint $table) use ($teams, $columnNames) {
            $table->uuid('id')->primary();
            $table->string('name'); // For spatie/laravel-permission
            $table->string('guard_name'); // For spatie/laravel-permission
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        // Permissions table
        Schema::create($tableNames['permissions'], function (Blueprint $table) use ($teams, $columnNames) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('guard_name');
            $table->string('group')->nullable();
            $table->string('display_name')->nullable();
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        // Role_has_permissions (pivot)
        Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames) {
            $table->foreignUuid('permission_id')->references('id')->on($tableNames['permissions'])->onDelete('cascade');
            $table->foreignUuid('role_id')->references('id')->on($tableNames['roles'])->onDelete('cascade');

            $table->primary(['permission_id', 'role_id']);

            $table->foreignUuid('team_id')->nullable();
        });

        // model_has_roles
        Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($teams, $columnNames, $tableNames) {
            $table->foreignUuid('role_id')->references('id')->on($tableNames['roles'])->onDelete('cascade');
            $table->string('morph_type');
            $table->uuid('morph_id');
            $table->index(['morph_id', 'morph_type', ]);

            if ($teams) {
                $table->foreignUuid($columnNames['team_foreign_key'])->nullable();
            }

            $table->primary(['role_id', 'morph_id', 'morph_type']);
        });

        // model_has_permissions
        Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($teams, $columnNames, $tableNames) {
            $table->foreignUuid('permission_id')->references('id')->on($tableNames['permissions'])->onDelete('cascade');
            $table->string('morph_type');
            $table->uuid('morph_id');
            $table->index(['morph_id', 'morph_type', ]);

            if ($teams) {
                $table->foreignUuid($columnNames['team_foreign_key'])->nullable();
            }

            $table->primary(['permission_id', 'morph_id', 'morph_type']);
        });
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        if (empty($tableNames)) return;

        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['permissions']);
        Schema::dropIfExists($tableNames['roles']);
    }
};
