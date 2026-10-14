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
        Schema::table('reviews', function (Blueprint $table) {
            // Decouple reviews: shop counter and delivery-man reviews do not require a product_id
            $table->unsignedBigInteger('product_id')->nullable()->change();

            if (!Schema::hasColumn('reviews', 'shop_id')) {
                $table->unsignedBigInteger('shop_id')->nullable()->index()->after('product_id');
            }
            if (!Schema::hasColumn('reviews', 'sentiment_tags')) {
                $table->json('sentiment_tags')->nullable()->after('comment');
            }
            if (!Schema::hasColumn('reviews', 'verified_location')) {
                $table->string('verified_location', 191)->nullable()->after('attachment');
            }
            if (!Schema::hasColumn('reviews', 'helpful_count')) {
                $table->unsignedInteger('helpful_count')->default(0)->after('status');
            }
            if (!Schema::hasColumn('reviews', 'is_disputed')) {
                $table->boolean('is_disputed')->default(false)->after('helpful_count');
            }
            if (!Schema::hasColumn('reviews', 'dispute_reason')) {
                $table->text('dispute_reason')->nullable()->after('is_disputed');
            }
            if (!Schema::hasColumn('reviews', 'dispute_status')) {
                $table->string('dispute_status', 32)->nullable()->after('dispute_reason');
            }
            if (!Schema::hasColumn('reviews', 'disputed_at')) {
                $table->timestamp('disputed_at')->nullable()->after('dispute_status');
            }
        });

        if (!Schema::hasTable('review_helpful_votes')) {
            Schema::create('review_helpful_votes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('review_id')->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable()->index();
                $table->timestamps();

                $table->unique(['review_id', 'customer_id'], 'rev_cust_helpful_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('review_helpful_votes')) {
            Schema::dropIfExists('review_helpful_votes');
        }

        Schema::table('reviews', function (Blueprint $table) {
            $columns = [
                'shop_id',
                'sentiment_tags',
                'verified_location',
                'helpful_count',
                'is_disputed',
                'dispute_reason',
                'dispute_status',
                'disputed_at'
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('reviews', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
