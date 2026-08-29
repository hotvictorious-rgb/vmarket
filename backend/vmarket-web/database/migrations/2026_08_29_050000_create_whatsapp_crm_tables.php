<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('whatsapp_conversations')) {
            Schema::create('whatsapp_conversations', function (Blueprint $table) {
                $table->id();
                $table->string('phone', 30)->index();
                $table->string('customer_name')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('status', 30)->default('open')->index();
                $table->string('priority', 30)->default('normal')->index();
                $table->string('assigned_department', 50)->default('general')->index();
                $table->unsignedBigInteger('assigned_agent_id')->nullable()->index();
                $table->timestamp('last_message_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('whatsapp_messages')) {
            Schema::create('whatsapp_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id')->index();
                $table->string('direction', 20)->default('inbound');
                $table->text('message')->nullable();
                $table->string('media_url')->nullable();
                $table->string('media_type')->nullable();
                $table->string('status', 30)->default('received');
                $table->string('sender_type', 30)->default('customer');
                $table->unsignedBigInteger('sender_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('whatsapp_broadcasts')) {
            Schema::create('whatsapp_broadcasts', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('message');
                $table->string('target_audience')->default('all');
                $table->string('status')->default('draft');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->integer('sent_count')->default(0);
                $table->integer('failed_count')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('whatsapp_broadcast_logs')) {
            Schema::create('whatsapp_broadcast_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('broadcast_id')->index();
                $table->string('phone', 30);
                $table->string('status', 30)->default('pending');
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('whatsapp_knowledge_base')) {
            Schema::create('whatsapp_knowledge_base', function (Blueprint $table) {
                $table->id();
                $table->string('question');
                $table->text('answer');
                $table->string('category')->default('general');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_knowledge_base');
        Schema::dropIfExists('whatsapp_broadcast_logs');
        Schema::dropIfExists('whatsapp_broadcasts');
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('whatsapp_conversations');
    }
};
