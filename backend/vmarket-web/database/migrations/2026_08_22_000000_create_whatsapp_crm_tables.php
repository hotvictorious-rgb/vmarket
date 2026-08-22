<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. WhatsApp Customer AI Relationship & Memory Profiles
        if (!Schema::hasTable('whatsapp_customer_ai_profiles')) {
            Schema::create('whatsapp_customer_ai_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('phone', 20)->unique()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('preferred_name', 100)->nullable();
                $table->enum('preferred_tone', ['pidgin_friendly', 'formal_english', 'casual_english'])->default('pidgin_friendly');
                $table->json('size_preferences')->nullable();
                $table->json('favorite_categories')->nullable();
                $table->json('favorite_colors')->nullable();
                $table->string('favorite_delivery_landmark', 255)->nullable();
                $table->string('preferred_payment_method', 50)->nullable();
                $table->enum('price_sensitivity', ['budget', 'moderate', 'luxury_vip'])->default('moderate');
                $table->json('interaction_memory_notes')->nullable();
                $table->json('predicted_reorder_categories')->nullable();
                $table->string('loyalty_tier', 50)->default('Bronze');
                $table->unsignedInteger('total_orders_count')->default(0);
                $table->decimal('total_spend_amount', 14, 2)->default(0.00);
                $table->timestamp('last_interaction_at')->nullable();
                $table->timestamps();
            });
        }

        // 2. WhatsApp Conversations Thread Table
        if (!Schema::hasTable('whatsapp_conversations')) {
            Schema::create('whatsapp_conversations', function (Blueprint $table) {
                $table->id();
                $table->string('phone', 20)->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('assigned_agent_id')->nullable()->index();
                $table->string('assigned_department', 50)->default('general');
                $table->enum('status', ['open', 'pending', 'resolved', 'bot_handling'])->default('bot_handling');
                $table->enum('priority', ['low', 'medium', 'high', 'urgent_dispute'])->default('medium');
                $table->string('customer_name', 150)->nullable();
                $table->json('tags')->nullable();
                $table->text('internal_notes')->nullable();
                $table->timestamp('last_message_at')->nullable()->index();
                $table->unsignedInteger('unread_agent_count')->default(0);
                $table->unsignedInteger('unread_customer_count')->default(0);
                $table->timestamp('locked_until')->nullable();
                $table->unsignedBigInteger('locked_by_agent_id')->nullable();
                $table->timestamps();
            });
        }

        // 3. WhatsApp Messages History Table
        if (!Schema::hasTable('whatsapp_messages')) {
            Schema::create('whatsapp_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id')->index();
                $table->string('meta_message_id', 100)->nullable()->unique();
                $table->enum('sender_type', ['customer', 'agent', 'bot', 'system'])->index();
                $table->unsignedBigInteger('sender_id')->nullable();
                $table->enum('message_type', ['text', 'image', 'audio', 'video', 'document', 'interactive_button', 'template'])->default('text');
                $table->text('message_body')->nullable();
                $table->string('media_url', 500)->nullable();
                $table->string('caption', 500)->nullable();
                $table->enum('delivery_status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending')->index();
                $table->text('error_reason')->nullable();
                $table->boolean('is_ai_draft')->default(false);
                $table->timestamps();

                $table->foreign('conversation_id')->references('id')->on('whatsapp_conversations')->onDelete('cascade');
            });
        }

        // 4. AI Knowledge Base FAQs Table
        if (!Schema::hasTable('whatsapp_faqs')) {
            Schema::create('whatsapp_faqs', function (Blueprint $table) {
                $table->id();
                $table->string('category', 50)->default('general');
                $table->text('question');
                $table->text('answer');
                $table->json('keywords')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('times_used')->default(0);
                $table->timestamps();
            });
        }

        // 5. AI Learning & Corrections Feedback Loop Table
        if (!Schema::hasTable('whatsapp_ai_corrections')) {
            Schema::create('whatsapp_ai_corrections', function (Blueprint $table) {
                $table->id();
                $table->text('customer_query');
                $table->text('ai_suggested_draft');
                $table->text('agent_final_reply');
                $table->unsignedBigInteger('agent_id');
                $table->boolean('is_approved_for_training')->default(true);
                $table->timestamps();
            });
        }

        // 6. Broadcast Campaigns Table
        if (!Schema::hasTable('whatsapp_broadcasts')) {
            Schema::create('whatsapp_broadcasts', function (Blueprint $table) {
                $table->id();
                $table->string('title', 150);
                $table->string('template_name', 100);
                $table->json('template_parameters')->nullable();
                $table->json('target_segment_filters')->nullable();
                $table->unsignedInteger('total_recipients')->default(0);
                $table->unsignedInteger('sent_count')->default(0);
                $table->unsignedInteger('delivered_count')->default(0);
                $table->unsignedInteger('read_count')->default(0);
                $table->unsignedInteger('replied_count')->default(0);
                $table->enum('status', ['draft', 'scheduled', 'processing', 'completed', 'canceled'])->default('draft');
                $table->timestamp('scheduled_for')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedBigInteger('created_by_admin_id');
                $table->timestamps();
            });
        }

        // 7. Broadcast Campaign Recipients Log Table
        if (!Schema::hasTable('whatsapp_broadcast_logs')) {
            Schema::create('whatsapp_broadcast_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('broadcast_id')->index();
                $table->string('phone', 20)->index();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('meta_message_id', 100)->nullable();
                $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed', 'opted_out'])->default('pending');
                $table->text('failure_reason')->nullable();
                $table->timestamps();

                $table->foreign('broadcast_id')->references('id')->on('whatsapp_broadcasts')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_broadcast_logs');
        Schema::dropIfExists('whatsapp_broadcasts');
        Schema::dropIfExists('whatsapp_ai_corrections');
        Schema::dropIfExists('whatsapp_faqs');
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('whatsapp_conversations');
        Schema::dropIfExists('whatsapp_customer_ai_profiles');
    }
};
