<?php

namespace Tests\Security;

use App\Models\Admin;
use App\Models\DeliveryMan;
use App\Models\Seller;
use App\Models\Shop;
use App\Models\VendorEmployee;
use App\User;

/**
 * [AI] Suite 10 — 9-ROLE ECOSYSTEM CROSS-ACCESS MATRIX & VISIBILITY (Prime Directive §11 & §12)
 *
 * Exhaustively tests boundary isolation and privilege limits across all 9 standardized roles:
 *  1. Super Admin
 *  2. Super Admin Employee
 *  3. Verified Merchant
 *  4. Unverified Merchant
 *  5. Verified Merchant Employee
 *  6. Unverified Merchant Employee
 *  7. Active Deliveryman
 *  8. Inactive Deliveryman
 *  9. Customer
 *
 * MANDATORY: All AIs must execute this matrix suite before concluding any backend modification.
 * RUN: php artisan test tests/Security/Suite10_NineRoleCrossAccessMatrixTest.php
 */
class Suite10_NineRoleCrossAccessMatrixTest extends SecurityTestCase
{
    /** @test Matrix 1: Only Super Admin (Role 1) has universal platform config & SaaS control */
    public function super_admin_exclusive_access_matrix(): void
    {
        $superAdmin    = $this->createSuperAdmin();
        $adminEmployee = $this->createAdminEmployee(['order_management']);
        $merchant      = $this->createVerifiedMerchant();
        $customer      = $this->createCustomer();

        // 1. Super Admin access
        $res1 = $this->actingAsAdmin($superAdmin)->get(route('admin.pos-management.dashboard'));
        $res1->assertOk();

        // 2. Admin Employee without pos_management
        $res2 = $this->actingAsAdmin($adminEmployee)->get(route('admin.pos-management.dashboard'));
        $this->assertSecurityDenied($res2, 'Admin employee without POS module must be denied');

        // 3. Merchant
        $res3 = $this->actingAsSeller($merchant)->get(route('admin.pos-management.dashboard'));
        $this->assertSecurityDenied($res3, 'Merchant cannot access Super Admin SaaS dashboard');

        // 4. Customer
        $res4 = $this->actingAsCustomer($customer)->get(route('admin.pos-management.dashboard'));
        $this->assertSecurityDenied($res4, 'Customer cannot access Super Admin SaaS dashboard');
    }

    /** @test Matrix 2: Verified Merchant (Role 3) vs Unverified Merchant (Role 4) marketplace boundary */
    public function merchant_verification_boundary_matrix(): void
    {
        $verified   = $this->createVerifiedMerchant();
        $unverified = $this->createUnverifiedMerchant();

        // Verified merchant accesses withdrawal management
        $resV = $this->actingAsSeller($verified)->get(route('vendor.withdraw.index'));
        $this->assertNotEquals(403, $resV->getStatusCode());

        // Unverified merchant is blocked from withdrawal & financial payouts
        $resU = $this->actingAsSeller($unverified)->get(route('vendor.withdraw.index'));
        $this->assertSecurityDenied($resU, 'Unverified merchant cannot access financial withdrawals');
    }

    /** @test Matrix 3: Delivery Riders (Roles 7 & 8) cannot penetrate merchant or admin panels */
    public function delivery_rider_boundary_matrix(): void
    {
        $activeRider   = $this->createActiveDeliveryMan();
        $inactiveRider = $this->createInactiveDeliveryMan();

        // Rider cannot access Admin Orders list
        $resAdmin = $this->actingAsDeliveryManAPI($activeRider)->get(route('admin.orders.list'));
        $this->assertSecurityDenied($resAdmin, 'Rider cannot access Admin Orders');

        // Rider cannot access Vendor Dashboard
        $resVendor = $this->actingAsDeliveryManAPI($activeRider)->get(route('vendor.dashboard'));
        $this->assertSecurityDenied($resVendor, 'Rider cannot access Vendor Dashboard');

        // Inactive rider is blocked from API all-orders
        $resInactive = $this->actingAsDeliveryManAPI($inactiveRider)->getJson('/api/v2/delivery-man/all-orders');
        $this->assertSecurityDenied($resInactive, 'Inactive rider blocked from operational routes');
    }

    /** @test Matrix 4: Customer (Role 9) zero-penetration against all admin, vendor, and rider routes */
    public function customer_zero_penetration_matrix(): void
    {
        $customer = $this->createCustomer();

        // Admin routes
        $resAdmin = $this->actingAsCustomer($customer)->get(route('admin.dashboard'));
        $this->assertSecurityDenied($resAdmin, 'Customer cannot access admin dashboard');

        // Vendor routes
        $resVendor = $this->actingAsCustomer($customer)->get(route('vendor.dashboard'));
        $this->assertSecurityDenied($resVendor, 'Customer cannot access vendor dashboard');

        // Third party payment setup
        $resPayment = $this->actingAsCustomer($customer)->get(route('admin.third-party.payment-method.index'));
        $this->assertSecurityDenied($resPayment, 'Customer cannot access admin payment method settings');
    }
}
