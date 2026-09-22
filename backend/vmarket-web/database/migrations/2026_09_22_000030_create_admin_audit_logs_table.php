<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [AI] Creates immutable admin audit logs table.
 *
 * Part of: VMarket Admin Panel Production Alignment Plan
 * Phase: A9 - Immutable Audit Logging System
 * Spec Reference: Sections 42, 43
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_audit_logs')) {
            Schema::create('admin_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('admin_id')->nullable()->index();
                $table->string('admin_name')->nullable();
                $table->string('admin_role')->nullable();
                $table->string('action')->index();
                $table->string('resource_type')->nullable()->index();
                $table->string('resource_id')->nullable()->index();
                $table->json('before_state')->nullable();
                $table->json('after_state')->nullable();
                $table->text('reason')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
    }
};
