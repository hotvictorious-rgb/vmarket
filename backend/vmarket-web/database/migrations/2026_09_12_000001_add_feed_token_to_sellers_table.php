<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            if (!Schema::hasColumn('sellers', 'feed_token')) {
                $table->string('feed_token', 64)->nullable()->unique()->index()->after('auth_token');
            }
            if (!Schema::hasColumn('sellers', 'feed_token_generated_at')) {
                $table->timestamp('feed_token_generated_at')->nullable()->after('feed_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            if (Schema::hasColumn('sellers', 'feed_token')) {
                $table->dropColumn('feed_token');
            }
            if (Schema::hasColumn('sellers', 'feed_token_generated_at')) {
                $table->dropColumn('feed_token_generated_at');
            }
        });
    }
};
