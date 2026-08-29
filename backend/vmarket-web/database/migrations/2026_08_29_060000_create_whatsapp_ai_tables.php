<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('whatsapp_faqs')) {
            Schema::create('whatsapp_faqs', function (Blueprint $table) {
                $table->id();
                $table->string('category', 50)->default('general')->index();
                $table->string('question');
                $table->text('answer');
                $table->json('keywords')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('times_used')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('whatsapp_ai_corrections')) {
            Schema::create('whatsapp_ai_corrections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('agent_id')->nullable()->index();
                $table->text('user_query')->nullable();
                $table->text('original_response')->nullable();
                $table->text('corrected_response')->nullable();
                $table->string('status', 30)->default('applied');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('whatsapp_customer_ai_profiles')) {
            Schema::create('whatsapp_customer_ai_profiles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('phone', 30)->nullable()->index();
                $table->json('preferences')->nullable();
                $table->json('purchase_intent')->nullable();
                $table->text('conversation_summary')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_customer_ai_profiles');
        Schema::dropIfExists('whatsapp_ai_corrections');
        Schema::dropIfExists('whatsapp_faqs');
    }
};
