<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [AI] Migration: Add unguessable cryptographic guest order access token.
     * Prevents sequential IDOR enumeration of order details, customer PII,
     * and pickup verification codes when accessed by unauthenticated guests.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'guest_access_token')) {
                $table->string('guest_access_token', 64)->nullable()->after('is_guest')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'guest_access_token')) {
                $table->dropIndex(['guest_access_token']);
                $table->dropColumn('guest_access_token');
            }
        });
    }
};
