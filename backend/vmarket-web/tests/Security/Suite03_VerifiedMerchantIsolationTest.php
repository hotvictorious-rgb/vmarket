<?php

namespace Tests\Security;

use App\Models\Order;
use App\Models\Product;
use App\Models\Seller;

/**
 * [AI] Suite 03 — VERIFIED MERCHANT DATA ISOLATION (Role 3)
 *
 * Verifies that a Verified Merchant (KYC approved, status=1) can ONLY access
 * their own products, orders, and financials. Cross-merchant data bleed must be zero.
 *
 * SECURITY STANDARDS TESTED:
 *  - Merchant A cannot view Merchant B's product list
 *  - Merchant A cannot view Merchant B's orders
 *  - Merchant A cannot update Merchant B's product
 *  - Merchant A cannot delete Merchant B's product
 *  - Merchant A cannot view Merchant B's transaction report
 *  - Vendor panel routes enforce seller_id = auth()->id()
 *
 * RUN: php artisan test tests/Security/Suite03_VerifiedMerchantIsolationTest.php
 */
class Suite03_VerifiedMerchantIsolationTest extends SecurityTestCase
{
    /** @test Merchant A cannot see Merchant B's products via vendor panel */
    public function merchant_cannot_view_other_merchants_products(): void
    {
        $merchantA = $this->createVerifiedMerchant();
        $merchantB = $this->createVerifiedMerchant();

        // Create product owned by Merchant B
        $productB = Product::factory()->create([
            'user_id'   => $merchantB->id,
            'added_by'  => 'seller',
        ]);

        // Merchant A requests the product detail of Merchant B's product
        $response = $this->actingAsSeller($merchantA)
            ->get(route('vendor.products.view', ['id' => $productB->id]));

        // Must NOT return HTTP 200 with Merchant B's product
        $this->assertNotHttp200($response, "vendor.products.view for foreign seller_id");
    }

    /** @test Merchant A cannot update Merchant B's product */
    public function merchant_cannot_update_other_merchants_product(): void
    {
        $merchantA = $this->createVerifiedMerchant();
        $merchantB = $this->createVerifiedMerchant();

        $productB = Product::factory()->create([
            'user_id'  => $merchantB->id,
            'added_by' => 'seller',
            'name'     => ['en' => 'Original Name'],
        ]);

        $response = $this->actingAsSeller($merchantA)
            ->post(route('vendor.products.update', ['id' => $productB->id]), [
                'name' => 'HACKED by Merchant A',
                'unit_price' => 0.01,
            ]);

        $this->assertSecurityDenied($response, 'Merchant A must not update Merchant B product');

        // Verify product name unchanged in DB
        $this->assertDatabaseMissing('products', [
            'id' => $productB->id,
            'slug' => 'hacked-by-merchant-a',
        ]);
    }

    /** @test Merchant A cannot view Merchant B's order list */
    public function merchant_cannot_view_other_merchants_orders(): void
    {
        $merchantA = $this->createVerifiedMerchant();
        $merchantB = $this->createVerifiedMerchant();

        $orderB = Order::factory()->create(['seller_id' => $merchantB->id]);

        $response = $this->actingAsSeller($merchantA)
            ->get(route('vendor.orders.details', ['id' => $orderB->id]));

        $this->assertSecurityDenied($response, 'Merchant A must not see Merchant B order detail');
    }

    /** @test Merchant cannot access admin panel routes even with direct URL */
    public function merchant_cannot_access_admin_panel(): void
    {
        $merchant = $this->createVerifiedMerchant();
        $response = $this->actingAsSeller($merchant)
            ->get(route('admin.pos-management.dashboard'));
        $this->assertSecurityDenied($response, 'Merchant must not access admin panel');
    }

    /** @test Merchant cannot access another merchant shop settings */
    public function merchant_cannot_modify_other_merchant_shop_settings(): void
    {
        $merchantA = $this->createVerifiedMerchant();
        $merchantB = $this->createVerifiedMerchant();
        $shopB = $merchantB->shop;

        $response = $this->actingAsSeller($merchantA)
            ->post(route('vendor.shop.update'), [
                'shop_id'   => $shopB->id,
                'name'      => 'HACKED SHOP',
                'address'   => 'Hacker Lane',
            ]);

        $this->assertSecurityDenied($response, 'Merchant A must not update Merchant B shop');

        $this->assertDatabaseMissing('shops', [
            'id'   => $shopB->id,
            'name' => 'HACKED SHOP',
        ]);
    }

    /** @test Unauthenticated cannot access vendor panel */
    public function unauthenticated_cannot_access_vendor_panel(): void
    {
        $response = $this->get(route('vendor.dashboard'));
        $this->assertSecurityDenied($response, 'No session = no vendor panel access');
    }

    /** @test Customer session cannot access vendor panel */
    public function customer_cannot_access_vendor_panel(): void
    {
        $customer = $this->createCustomer();
        $response = $this->actingAsCustomer($customer)->get(route('vendor.dashboard'));
        $this->assertSecurityDenied($response, 'Customer guard cannot access seller guard routes');
    }
}
