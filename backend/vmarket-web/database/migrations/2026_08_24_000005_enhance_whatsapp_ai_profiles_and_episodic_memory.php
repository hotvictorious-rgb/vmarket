<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [AI] Migration: Enhance WhatsApp AI Profiles with Episodic Memory & Human Auto-Resume
     */
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_customer_ai_profiles')) {
            Schema::table('whatsapp_customer_ai_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('whatsapp_customer_ai_profiles', 'episodic_memory')) {
                    $table->json('episodic_memory')->nullable()->after('size_preferences');
                }
                if (!Schema::hasColumn('whatsapp_customer_ai_profiles', 'last_human_agent_name')) {
                    $table->string('last_human_agent_name', 100)->nullable()->after('episodic_memory');
                }
                if (!Schema::hasColumn('whatsapp_customer_ai_profiles', 'last_human_interaction_at')) {
                    $table->timestamp('last_human_interaction_at')->nullable()->after('last_human_agent_name');
                }
                if (!Schema::hasColumn('whatsapp_customer_ai_profiles', 'unanswered_customer_since')) {
                    $table->timestamp('unanswered_customer_since')->nullable()->after('last_human_interaction_at');
                }
                if (!Schema::hasColumn('whatsapp_customer_ai_profiles', 'auto_resume_enabled')) {
                    $table->boolean('auto_resume_enabled')->default(true)->after('unanswered_customer_since');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('whatsapp_customer_ai_profiles')) {
            Schema::table('whatsapp_customer_ai_profiles', function (Blueprint $table) {
                $table->dropColumn([
                    'episodic_memory',
                    'last_human_agent_name',
                    'last_human_interaction_at',
                    'unanswered_customer_since',
                    'auto_resume_enabled',
                ]);
            });
        }
    }
};
