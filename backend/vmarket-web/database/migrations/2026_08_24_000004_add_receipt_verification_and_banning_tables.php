<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [AI] Migration: Receipt Verification, Anti-Duplicate Session IDs & Customer Blacklist
     */
    public function up(): void
    {
        // 1. Add receipt verification and session ID tracking to orders table
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'bank_session_id')) {
                    $table->string('bank_session_id', 100)->nullable()->index()->after('transaction_ref');
                }
                if (!Schema::hasColumn('orders', 'receipt_image')) {
                    $table->string('receipt_image', 255)->nullable()->after('bank_session_id');
                }
                if (!Schema::hasColumn('orders', 'receipt_metadata')) {
                    $table->json('receipt_metadata')->nullable()->after('receipt_image');
                }
                if (!Schema::hasColumn('orders', 'receipt_verified_by')) {
                    $table->unsignedBigInteger('receipt_verified_by')->nullable()->after('receipt_metadata');
                }
                if (!Schema::hasColumn('orders', 'receipt_verified_at')) {
                    $table->timestamp('receipt_verified_at')->nullable()->after('receipt_verified_by');
                }
            });
        }

        // 2. Create blacklisted_customers table
        if (!Schema::hasTable('blacklisted_customers')) {
            Schema::create('blacklisted_customers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('phone', 30)->index();
                $table->string('email', 100)->nullable()->index();
                $table->string('ip_address', 50)->nullable()->index();
                $table->text('reason')->nullable();
                $table->unsignedBigInteger('banned_by')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex(['bank_session_id']);
                $table->dropColumn([
                    'bank_session_id',
                    'receipt_image',
                    'receipt_metadata',
                    'receipt_verified_by',
                    'receipt_verified_at'
                ]);
            });
        }

        Schema::dropIfExists('blacklisted_customers');
    }
};
