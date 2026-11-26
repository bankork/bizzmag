<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Staff sign in with an email or a phone number (P1 Q1), so email
     * becomes optional and at least one of the two is required.
     * Users are disabled, never deleted (FR-AUTH-005).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone', 20)->nullable()->unique()->after('email');
            $table->boolean('is_active')->default(true)->after('password');
            $table->boolean('must_change_password')->default(false)->after('is_active');
            $table->timestamp('password_changed_at')->nullable()->after('must_change_password');
            $table->timestamp('last_login_at')->nullable()->after('password_changed_at');
            $table->timestamp('disabled_at')->nullable()->after('last_login_at');
            $table->foreignId('disabled_by')->nullable()->after('disabled_at')->constrained('users')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE users ADD CONSTRAINT users_email_or_phone CHECK (email IS NOT NULL OR phone IS NOT NULL)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CHECK users_email_or_phone');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('disabled_by');
            $table->dropUnique(['phone']);
            $table->dropColumn(['phone', 'is_active', 'must_change_password', 'password_changed_at', 'last_login_at', 'disabled_at']);
        });
    }
};
