<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The salon. Every business-owned record is scoped to one of these
     * (FR-IAM-007). An installation normally has a single business.
     */
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->char('currency', 3)->default('NGN');
            $table->string('timezone', 64)->default('Africa/Lagos');
            $table->string('phone', 20)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('receipt_footer', 500)->nullable();
            $table->string('invoice_footer', 500)->nullable();
            $table->string('logo_path')->nullable();
            $table->unsignedInteger('settings_version')->default(1);
            $table->timestamps();
        });

        Schema::table('audit_events', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audit_events', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
        });

        Schema::dropIfExists('businesses');
    }
};
