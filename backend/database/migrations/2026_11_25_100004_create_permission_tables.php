<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * spatie/laravel-permission tables with teams switched on. The team is
     * the business, so every role and role assignment belongs to one
     * business (Stage 3 §7.2). Permissions are a global catalogue.
     *
     * Roles carry their discount caps (FR-IAM-009): null means unlimited.
     * system_key marks the four default roles so code can recognise the
     * Owner role even after it is renamed.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('guard_name', 50);
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->string('guard_name', 50);
            $table->string('system_key', 30)->nullable();
            $table->string('description', 255)->nullable();
            $table->unsignedInteger('discount_cap_bp')->nullable();
            $table->unsignedBigInteger('discount_cap_kobo')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'name', 'guard_name']);
            $table->unique(['business_id', 'system_key']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger('business_id');

            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
            $table->index('business_id', 'model_has_permissions_team_foreign_key_index');
            $table->primary(['business_id', 'permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger('business_id');

            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->index('business_id', 'model_has_roles_team_foreign_key_index');
            $table->primary(['business_id', 'role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();

            $table->primary(['permission_id', 'role_id'], 'role_has_permissions_permission_id_role_id_primary');
        });

        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
