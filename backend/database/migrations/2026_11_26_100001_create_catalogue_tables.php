<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What the salon sells (FR-SVC-001 to 009). Items are deactivated, never
     * deleted, so old visits keep pointing at them. A variant is an item with
     * a parent; it carries a copy of its parent's taxable flag, quantity flag
     * and credit split, kept in step by the catalogue actions.
     */
    public function up(): void
    {
        Schema::create('catalogue_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->string('type', 10);
            $table->string('name', 80);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['business_id', 'type', 'name']);
            $table->index(['business_id', 'type', 'sort_order']);
        });

        Schema::create('catalogue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained('catalogue_categories')->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('catalogue_items')->restrictOnDelete();
            $table->unsignedBigInteger('parent_key')->storedAs('COALESCE(parent_id, 0)');
            $table->string('type', 10);
            $table->string('name', 100);
            $table->string('description', 500)->nullable();
            $table->boolean('has_variants')->default(false);
            $table->unsignedBigInteger('price_kobo')->nullable();
            $table->boolean('taxable')->default(true);
            $table->boolean('quantity_allowed')->default(false);
            $table->string('allocation_method', 20)->nullable();
            $table->unsignedSmallInteger('primary_share_bp')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['business_id', 'category_id', 'parent_key', 'name'], 'catalogue_items_unique_name');
            $table->index(['business_id', 'is_active']);
        });

        DB::statement('ALTER TABLE catalogue_items ADD CONSTRAINT catalogue_items_primary_share CHECK (primary_share_bp IS NULL OR primary_share_bp BETWEEN 1 AND 10000)');
        DB::statement('ALTER TABLE catalogue_items ADD CONSTRAINT catalogue_items_price CHECK (price_kobo IS NOT NULL OR has_variants = 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogue_items');
        Schema::dropIfExists('catalogue_categories');
    }
};
