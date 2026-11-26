<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Editable configuration (FR-BIZ-005) and the single tax (FR-TAX-001,
     * FR-TAX-008). Tax starts switched off. Both carry a version so a save
     * based on stale values is refused instead of overwriting.
     */
    public function up(): void
    {
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->string('key', 80);
            $table->json('value');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['business_id', 'key']);
        });

        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->restrictOnDelete();
            $table->boolean('enabled')->default(false);
            $table->string('name', 30)->default('VAT');
            $table->unsignedInteger('rate_bp')->default(750);
            $table->string('mode', 10)->default('exclusive');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_settings');
        Schema::dropIfExists('business_settings');
    }
};
