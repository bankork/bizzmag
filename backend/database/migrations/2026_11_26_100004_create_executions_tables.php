<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Work actually done (SRS §4.6, §4.7, §6.3). A record is never edited in
     * place or deleted: a correction supersedes it and a cancellation changes
     * its status (BR-005, BR-006).
     *
     * active_item_key is the item's ID while the execution is active
     * (Confirmed or Disputed) and NULL otherwise. Its unique index lets the
     * database itself refuse a second active execution for one item
     * (§7.7.3), whatever the application code does.
     */
    public function up(): void
    {
        Schema::create('executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_item_id')->constrained()->restrictOnDelete();
            $table->string('status', 12);
            $table->unsignedBigInteger('active_item_key')->nullable()
                ->storedAs("IF(status IN ('confirmed', 'disputed'), order_item_id, NULL)");
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at');
            $table->boolean('late')->default(false);
            $table->foreignId('supersedes_id')->nullable()->constrained('executions')->restrictOnDelete();
            $table->string('allocation_method', 20);
            $table->unsignedSmallInteger('primary_share_bp')->nullable();
            $table->string('override_reason', 500)->nullable();
            $table->foreignId('overridden_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('overridden_at')->nullable();
            $table->string('dispute_reason', 500)->nullable();
            $table->foreignId('disputed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('disputed_at')->nullable();
            $table->string('resolution_reason', 500)->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique('active_item_key');
            $table->index(['order_item_id', 'status']);
            $table->index(['business_id', 'submitted_at']);
        });

        DB::statement('ALTER TABLE executions ADD CONSTRAINT executions_primary_share CHECK (primary_share_bp IS NULL OR primary_share_bp BETWEEN 1 AND 10000)');

        Schema::create('execution_contributors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('execution_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employee_profiles')->restrictOnDelete();
            $table->boolean('is_primary');
            $table->unsignedSmallInteger('share_bp');
            $table->string('joined_via', 12);
            $table->timestamp('created_at');

            $table->unique(['execution_id', 'employee_id']);
            $table->index('employee_id');
        });

        DB::statement('ALTER TABLE execution_contributors ADD CONSTRAINT execution_contributors_share CHECK (share_bp BETWEEN 1 AND 10000)');
    }

    public function down(): void
    {
        Schema::dropIfExists('execution_contributors');
        Schema::dropIfExists('executions');
    }
};
