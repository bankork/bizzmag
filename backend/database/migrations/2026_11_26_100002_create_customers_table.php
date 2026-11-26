<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registered customers (FR-CUS-001 to 009). Phone numbers are stored in
     * E.164 form. The same number on two customers is a warning, not an
     * error, so the phone index is not unique. Customers are never deleted;
     * a removal request clears their personal data (SEC-021).
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->string('phone_e164', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('notes', 1000)->nullable();
            $table->timestamp('anonymised_at')->nullable();
            $table->foreignId('anonymised_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['business_id', 'phone_e164']);
            $table->index(['business_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
