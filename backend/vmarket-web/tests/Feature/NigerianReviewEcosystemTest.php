<?php

namespace Tests\Feature;

require_once __DIR__ . '/DumpSchemaTestCase.php';

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewHelpfulVote;
use App\Models\Seller;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NigerianReviewEcosystemTest extends DumpSchemaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function ($table) {
                $table->id();
                $table->unsignedBigInteger('product_id')->nullable();
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('delivery_man_id')->nullable();
                $table->unsignedBigInteger('order_id')->nullable();
                $table->text('comment')->nullable();
                $table->json('attachment')->nullable();
                $table->integer('rating')->default(5);
                $table->integer('status')->default(1);
                $table->boolean('is_saved')->default(false);
                $table->timestamps();
            });
        }

        // Apply ecosystem migration
        (require base_path('database/migrations/2026_10_14_000002_add_nigerian_review_ecosystem_to_reviews_table.php'))->up();

        config(['auth.guards.api.driver' => 'session']);
    }

    protected function createFixture(): array
    {
        $customer = User::create([
            'name' => 'Chinedu Eze',
            'f_name' => 'Chinedu',
            'l_name' => 'Eze',
            'email' => 'chinedu_' . uniqid() . '@example.com',
            'phone' => '080' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
        ]);

        $seller = Seller::create([
            'f_name' => 'Emeka',
            'l_name' => 'IkejaTrader',
            'phone' => '081' . rand(10000000, 99999999),
            'email' => 'emeka_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'status' => 'approved',
        ]);

        $shop = Shop::create([
            'seller_id' => $seller->id,
            'name' => 'Emeka Electronics Plaza',
            'address' => 'Shop 12, Computer Village, Ikeja',
            'contact' => '08123456789',
            'banner' => 'def.png',
        ]);

        $product = Product::create([
            'name' => 'Original Fast Charger 65W',
            'slug' => 'original-fast-charger-' . uniqid(),
            'added_by' => 'seller',
            'user_id' => $seller->id,
            'unit_price' => 15000,
            'purchase_price' => 10000,
            'current_stock' => 50,
            'status' => 1,
            'thumbnail' => 'thumb.png',
            'images' => '[]',
            'color_image' => '[]',
            'category_ids' => '[]',
            'choice_options' => '[]',
            'variation' => '[]',
            'attributes' => '[]',
            'colors' => '[]',
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'customer_type' => 'customer',
            'payment_status' => 'paid',
            'order_status' => 'delivered',
            'order_type' => 'pickup',
            'order_amount' => 15000,
            'seller_id' => $seller->id,
            'seller_is' => 'seller',
        ]);

        OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'seller_id' => $seller->id,
            'product_details' => json_encode($product->toArray()),
            'qty' => 1,
            'price' => 15000,
            'tax' => 0,
            'discount' => 0,
            'delivery_status' => 'delivered',
            'payment_status' => 'paid',
        ]);

        return compact('customer', 'seller', 'shop', 'product', 'order');
    }

    public function test_get_sentiment_chips_returns_nigerian_categories(): void
    {
        $response = $this->getJson('/api/v1/products/reviews/sentiment-chips');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'product' => ['positive', 'negative'],
                'shop' => ['positive', 'negative'],
                'delivery' => ['positive', 'negative'],
            ],
        ]);

        $data = $response->json('data');
        $this->assertContains('100% Original', $data['product']['positive']);
        $this->assertContains('Fast Counter Service', $data['shop']['positive']);
        $this->assertContains('Courteous Rider', $data['delivery']['positive']);
    }

    public function test_customer_can_submit_product_review_with_sentiment_tags_and_verified_location(): void
    {
        $f = $this->createFixture();

        $response = $this->actingAs($f['customer'], 'api')->postJson('/api/v1/products/reviews/submit', [
            'product_id' => $f['product']->id,
            'order_id' => $f['order']->id,
            'rating' => 5,
            'comment' => 'Working perfectly. Tested and trusted!',
            'sentiment_tags' => ['100% Original', 'Neat Packaging', 'Long Battery Life'],
        ]);

        $response->assertStatus(200);

        $review = Review::where('product_id', $f['product']->id)->where('order_id', $f['order']->id)->first();
        $this->assertNotNull($review);
        $this->assertEquals(5, $review->rating);
        $this->assertEquals(['100% Original', 'Neat Packaging', 'Long Battery Life'], $review->sentiment_tags);
        $this->assertStringContainsString('Verified In-Shop Pickup', $review->verified_location);
        $this->assertEquals(0, $review->helpful_count);
        $this->assertFalse((bool) $review->is_disputed);
    }

    public function test_customer_can_submit_in_shop_counter_experience_review(): void
    {
        $f = $this->createFixture();

        $response = $this->actingAs($f['customer'], 'api')->postJson('/api/v1/products/reviews/shop/submit', [
            'order_id' => $f['order']->id,
            'rating' => 5,
            'comment' => 'The attendant was very polite and tested the charger before I left the shop.',
            'sentiment_tags' => ['Fast Counter Service', 'Very Polite Attendant', 'Allowed In-Store Testing'],
        ]);

        $response->assertStatus(200);

        $shopReview = Review::where('shop_id', $f['shop']->id)->where('order_id', $f['order']->id)->first();
        $this->assertNotNull($shopReview);
        $this->assertNull($shopReview->product_id);
        $this->assertEquals(5, $shopReview->rating);
        $this->assertEquals(['Fast Counter Service', 'Very Polite Attendant', 'Allowed In-Store Testing'], $shopReview->sentiment_tags);
        $this->assertStringContainsString('Emeka Electronics Plaza', $shopReview->verified_location);
    }

    public function test_customer_can_submit_delivery_man_review_with_sentiment_tags(): void
    {
        $f = $this->createFixture();

        $deliveryManId = DB::table('delivery_men')->insertGetId([
            'f_name' => 'Tunde',
            'l_name' => 'Rider',
            'phone' => '08099887766',
            'email' => 'tunde_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);

        $order = Order::create([
            'customer_id' => $f['customer']->id,
            'customer_type' => 'customer',
            'payment_status' => 'paid',
            'order_status' => 'delivered',
            'order_type' => 'default_type',
            'order_amount' => 15000,
            'seller_id' => $f['seller']->id,
            'seller_is' => 'seller',
            'delivery_man_id' => $deliveryManId,
        ]);

        $response = $this->actingAs($f['customer'], 'api')->postJson('/api/v1/products/delivery-man/reviews/submit', [
            'order_id' => $order->id,
            'rating' => 5,
            'comment' => 'The dispatch rider was very courteous and arrived on time.',
            'sentiment_tags' => ['Courteous Rider', 'Careful Package Handling', 'Fast Dispatch'],
        ]);

        $response->assertStatus(200);

        $dmReview = Review::where('delivery_man_id', $deliveryManId)->where('order_id', $order->id)->first();
        $this->assertNotNull($dmReview);
        $this->assertNull($dmReview->product_id);
        $this->assertEquals(5, $dmReview->rating);
        $this->assertEquals(['Courteous Rider', 'Careful Package Handling', 'Fast Dispatch'], $dmReview->sentiment_tags);
    }

    public function test_helpful_vote_deduplication(): void
    {
        $f = $this->createFixture();

        $review = Review::create([
            'id' => 889911,
            'product_id' => $f['product']->id,
            'customer_id' => $f['customer']->id,
            'order_id' => $f['order']->id,
            'rating' => 5,
            'comment' => 'Authentic item!',
            'status' => 1,
            'helpful_count' => 0,
        ]);

        // First vote succeeds
        $res1 = $this->actingAs($f['customer'], 'api')->postJson("/api/v1/products/reviews/{$review->id}/helpful");
        $res1->assertStatus(200);
        $this->assertTrue($res1->json('status'));
        $this->assertEquals(1, $res1->json('helpful_count'));

        // Second vote by same customer is rejected (no duplicate vote inflation)
        $res2 = $this->actingAs($f['customer'], 'api')->postJson("/api/v1/products/reviews/{$review->id}/helpful");
        $res2->assertStatus(200);
        $this->assertFalse($res2->json('status'));
        $this->assertEquals(1, $review->fresh()->helpful_count);
    }

    public function test_vendor_can_dispute_malicious_review(): void
    {
        $f = $this->createFixture();

        $review = Review::create([
            'id' => 991122,
            'product_id' => $f['product']->id,
            'customer_id' => $f['customer']->id,
            'order_id' => $f['order']->id,
            'rating' => 1,
            'comment' => 'Fake seller! Competitor store is better!',
            'status' => 1,
        ]);

        // Vendor disputes via API v3
        $request = new \Illuminate\Http\Request([
            'review_id' => $review->id,
            'reason' => 'Competitor stall sabotage. Customer never reported any issue and refused testing.',
        ]);
        $request->seller = $f['seller'];

        $sellerController = app(\App\Http\Controllers\RestAPI\v3\seller\SellerController::class);
        $response = $sellerController->shopProductReviewDispute($request);

        $this->assertEquals(200, $response->getStatusCode());
        $fresh = $review->fresh();
        $this->assertTrue((bool) $fresh->is_disputed);
        $this->assertEquals('pending', $fresh->dispute_status);
        $this->assertEquals('Competitor stall sabotage. Customer never reported any issue and refused testing.', $fresh->dispute_reason);
        $this->assertNotNull($fresh->disputed_at);
    }

    public function test_admin_can_resolve_review_dispute(): void
    {
        $f = $this->createFixture();

        $review = Review::create([
            'id' => 771122,
            'product_id' => $f['product']->id,
            'customer_id' => $f['customer']->id,
            'order_id' => $f['order']->id,
            'rating' => 1,
            'comment' => 'Blackmail attempt review',
            'status' => 1,
            'is_disputed' => true,
            'dispute_status' => 'pending',
        ]);

        $adminController = app(\App\Http\Controllers\Admin\Product\ReviewController::class);

        // Resolution 1: Quarantine defamatory review
        $req = new \Illuminate\Http\Request([
            'id' => $review->id,
            'resolution' => 'quarantined',
        ]);
        $adminController->resolveDispute($req);

        $quarantined = Review::withoutGlobalScope('active')->find($review->id);
        $this->assertEquals(0, $quarantined->status);
        $this->assertEquals('resolved_quarantined', $quarantined->dispute_status);

        // Resolution 2: Upheld review
        $req2 = new \Illuminate\Http\Request([
            'id' => $review->id,
            'resolution' => 'upheld',
        ]);
        $adminController->resolveDispute($req2);

        $upheld = Review::withoutGlobalScope('active')->find($review->id);
        $this->assertEquals(1, $upheld->status);
        $this->assertEquals('resolved_upheld', $upheld->dispute_status);
    }

    public function test_zero_cashback_promised_or_credited(): void
    {
        $f = $this->createFixture();

        $initialWallet = DB::table('customer_wallets')->where('customer_id', $f['customer']->id)->value('balance') ?? 0;

        $response = $this->actingAs($f['customer'], 'api')->postJson('/api/v1/products/reviews/submit', [
            'product_id' => $f['product']->id,
            'order_id' => $f['order']->id,
            'rating' => 5,
            'comment' => 'Excellent charger',
            'sentiment_tags' => ['100% Original'],
        ]);
        $response->assertStatus(200);

        // Verify zero wallet change / zero cashback credited
        $finalWallet = DB::table('customer_wallets')->where('customer_id', $f['customer']->id)->value('balance') ?? 0;
        $this->assertEquals($initialWallet, $finalWallet, 'Zero cashback must be awarded for reviews');
        $this->assertNull($response->json('cashback_earned'));
    }
}
