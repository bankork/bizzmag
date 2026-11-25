<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gap-free document numbering (TDR-10, DM-10).
     *
     * A row is locked FOR UPDATE and incremented inside the business
     * transaction, so a rollback also rolls the number back.
     * business_id 0 is used for sequences that are not scoped to a business;
     * the foreign key to businesses is added in Phase 1.
     */
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->string('sequence_key', 50);
            $table->string('period', 16)->default('');
            $table->unsignedBigInteger('next_value')->default(1);
            $table->timestamps();

            $table->unique(['business_id', 'sequence_key', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};
