<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only accountability log (TDR-11, FR-AUD-001 to FR-AUD-010).
     *
     * business_id, device_id and order_id get foreign keys once their tables
     * exist (Phases 1, 3 and 8). prev_hash and hash are filled by the hash
     * chain in Phase 8 (SEC-024).
     */
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('device_id')->nullable();
            $table->string('action', 100);
            $table->string('subject_type', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->text('reason')->nullable();
            $table->boolean('sod_exempt')->default(false);
            $table->string('request_id', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->char('prev_hash', 64)->nullable();
            $table->char('hash', 64)->nullable();
            $table->timestamp('created_at', 6)->useCurrent();

            $table->index(['business_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('order_id');
            $table->index(['actor_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
