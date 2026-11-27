<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Visits and what was asked for (SRS §4.5, §6.1, §6.2). Each item keeps a
     * snapshot of the catalogue at the moment it was added: name, type,
     * price, taxable flag and credit split (§7.4, P3 Q4). Nothing is deleted;
     * items and visits change only through their status.
     *
     * order_items carries business_id as well as its order, so an item looked
     * up by ID is scoped to the business by the same fail-closed scope.
     */
    public function up(): void
    {
        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->string('reference', 40);
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('guest_name', 100)->nullable();
            $table->string('guest_phone_e164', 20)->nullable();
            $table->string('status', 12)->default('open');
            $table->timestamp('opened_at');
            $table->date('opened_business_day');
            $table->timestamp('finalised_at')->nullable();
            $table->date('finalised_business_day')->nullable();
            $table->timestamp('late_window_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('cancel_reason', 500)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['business_id', 'reference']);
            $table->index(['business_id', 'status', 'opened_business_day']);
            $table->index(['business_id', 'finalised_business_day']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('catalogue_item_id')->constrained()->restrictOnDelete();
            $table->string('type', 10);
            $table->string('name_snapshot', 210);
            $table->unsignedBigInteger('unit_price_kobo');
            $table->boolean('taxable_snapshot');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->string('allocation_method_snapshot', 20)->nullable();
            $table->unsignedSmallInteger('primary_share_bp_snapshot')->nullable();
            $table->unsignedBigInteger('override_price_kobo')->nullable();
            $table->string('override_reason', 500)->nullable();
            $table->foreignId('override_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('override_at')->nullable();
            $table->string('status', 20);
            $table->string('origin', 20);
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('request_note', 500)->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_reason', 500)->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['service_order_id', 'status']);
            $table->index(['business_id', 'status']);
        });

        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_quantity CHECK (quantity >= 1)');
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_primary_share CHECK (primary_share_bp_snapshot IS NULL OR primary_share_bp_snapshot BETWEEN 1 AND 10000)');

        Schema::create('order_item_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employee_profiles')->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at');

            $table->unique(['order_item_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_assignments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('service_orders');
    }
};
