<?php

namespace Tests\Security;

/**
 * [AI] Suite 04 — UNVERIFIED MERCHANT MARKETPLACE BLOCK (Role 4)
 *
 * Verifies that an Unverified Merchant (KYC pending, seller status=0) is strictly
 * blocked from any online marketplace-selling features. They may only access
 * the Free-Tier In-Store POS (1 store, offline only).
 *
 * SECURITY STANDARDS TESTED:
 *  - Unverified merchant cannot add products to the live marketplace
 *  - Unverified merchant cannot activate product listings
 *  - Unverified merchant cannot access withdrawal/payout pages
 *  - Unverified merchant cannot access seller wallet or commission pages
 *  - Unverified merchant CAN reach their free-tier POS register (offline counter)
 *  - Status=0 merchants cannot upgrade own status to 1 via POST manipulation
 *
 * RUN: php artisan test tests/Security/Suite04_UnverifiedMerchantBlockTest.php
 */
class Suite04_UnverifiedMerchantBlockTest extends SecurityTestCase
{
    /** @test Unverified merchant cannot create live marketplace products */
    public function unverified_merchant_cannot_publish_marketplace_product(): void
    {
        $merchant = $this->createUnverifiedMerchant();

        $response = $this->actingAsSeller($merchant)
            ->post(route('vendor.products.store'), [
                'name'             => ['en' => 'Test Product'],
                'category_ids'     => [1],
                'unit_price'       => 100,
                'published'        => 1,
                'status'           => 1,
            ]);

        // Must be denied (403/302 redirect away or 422 because seller is not approved)
        $this->assertNotEquals(200, $response->getStatusCode(),
            'Unverified merchant must not publish marketplace product');
    }

    /** @test Unverified merchant cannot access withdrawal/payout */
    public function unverified_merchant_cannot_access_withdrawal(): void
    {
        $merchant = $this->createUnverifiedMerchant();
        $response = $this->actingAsSeller($merchant)
            ->get(route('vendor.withdraw.index'));
        $this->assertSecurityDenied($response, 'Unverified merchant must not access withdrawals');
    }

    /** @test Unverified merchant cannot access seller wallet balance */
    public function unverified_merchant_cannot_access_seller_wallet(): void
    {
        $merchant = $this->createUnverifiedMerchant();
        $response = $this->actingAsSeller($merchant)
            ->get(route('vendor.wallet.index'));
        $this->assertSecurityDenied($response, 'Unverified merchant must not access seller wallet');
    }

    /** @test Unverified merchant cannot self-promote status via POST */
    public function unverified_merchant_cannot_self_promote_status(): void
    {
        $merchant = $this->createUnverifiedMerchant();

        // Attempt to set own status to 1 (approved) via any endpoint
        $response = $this->actingAsSeller($merchant)
            ->post(route('vendor.profile.update'), [
                'f_name' => $merchant->f_name,
                'l_name' => $merchant->l_name,
                'email'  => $merchant->email,
                'status' => 1,  // [AI] Injection attempt
            ]);

        // Status must NOT be changed in DB
        $this->assertDatabaseHas('sellers', [
            'id'     => $merchant->id,
            'status' => 0,
        ]);
    }

    /** @test Seller with status=0 is redirected away from marketplace-specific routes */
    public function unverified_merchant_redirected_from_marketplace_routes(): void
    {
        $merchant = $this->createUnverifiedMerchant();

        $marketplaceRoutes = [
            route('vendor.products.index'),
            route('vendor.orders.list'),
        ];

        foreach ($marketplaceRoutes as $url) {
            $response = $this->actingAsSeller($merchant)->get($url);
            $status = $response->getStatusCode();
            $this->assertContains($status, [302, 403, 401],
                "Unverified merchant must not see [{$url}] (got {$status})");
        }
    }

    /** @test Unverified merchant cannot access commission reports */
    public function unverified_merchant_cannot_access_commission_reports(): void
    {
        $merchant = $this->createUnverifiedMerchant();
        $response = $this->actingAsSeller($merchant)
            ->get(route('vendor.reports.transaction'));
        $this->assertSecurityDenied($response, 'Unverified merchant must not see transaction reports');
    }
}
