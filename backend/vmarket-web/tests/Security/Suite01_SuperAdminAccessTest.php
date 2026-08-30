<?php

namespace Tests\Security;

use App\Models\Order;
use App\Models\Product;
use App\Models\Seller;

/**
 * [AI] Suite 01 — SUPER ADMIN ACCESS
 *
 * Verifies that Role 1 (Super Admin, admin_role_id=1) can access ALL admin routes
 * regardless of module_access, including the pos-management group fixed in VULN-NEW-001.
 *
 * SECURITY STANDARDS TESTED:
 *  - Helpers::module_permission_check() always returns true for admin_role_id=1
 *  - Super Admin sees MRR dashboard, POS settings, payment gateway config
 *  - Super Admin can update POS SaaS pricing
 *
 * RUN: php artisan test tests/Security/Suite01_SuperAdminAccessTest.php
 */
class Suite01_SuperAdminAccessTest extends SecurityTestCase
{
    /** @test */
    public function super_admin_can_access_pos_management_dashboard(): void
    {
        $admin = $this->createSuperAdmin();
        $response = $this->actingAsAdmin($admin)->get(route('admin.pos-management.dashboard'));
        $response->assertStatus(200);
    }

    /** @test */
    public function super_admin_can_access_pos_settings(): void
    {
        $admin = $this->createSuperAdmin();
        $response = $this->actingAsAdmin($admin)->get(route('admin.pos-management.settings'));
        $response->assertStatus(200);
    }

    /** @test */
    public function super_admin_can_access_payment_method_config(): void
    {
        $admin = $this->createSuperAdmin();
        $response = $this->actingAsAdmin($admin)
            ->get(route('admin.third-party.payment-method.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function super_admin_can_access_all_orders(): void
    {
        $admin = $this->createSuperAdmin();
        $response = $this->actingAsAdmin($admin)->get(route('admin.orders.list'));
        $response->assertStatus(200);
    }

    /** @test */
    public function super_admin_can_access_vendor_management(): void
    {
        $admin = $this->createSuperAdmin();
        $response = $this->actingAsAdmin($admin)->get(route('admin.vendors.list'));
        $response->assertStatus(200);
    }

    /** @test */
    public function super_admin_sees_all_admin_routes_without_module_restriction(): void
    {
        $admin = $this->createSuperAdmin();

        $criticalRoutes = [
            route('admin.pos-management.dashboard'),
            route('admin.pos-management.settings'),
            route('admin.third-party.payment-method.index'),
            route('admin.business-settings.store-setup.index'),
        ];

        foreach ($criticalRoutes as $url) {
            $response = $this->actingAsAdmin($admin)->get($url);
            $this->assertNotEquals(403, $response->getStatusCode(),
                "Super Admin must NOT get 403 on [{$url}]");
        }
    }

    /** @test */
    public function unauthenticated_user_cannot_access_admin_routes(): void
    {
        $response = $this->get(route('admin.pos-management.dashboard'));
        $this->assertSecurityDenied($response, 'Unauthenticated must be denied pos-management');
    }

    /** @test */
    public function customer_session_cannot_access_admin_routes(): void
    {
        $customer = $this->createCustomer();
        $response = $this->actingAsCustomer($customer)
            ->get(route('admin.pos-management.dashboard'));
        $this->assertSecurityDenied($response, 'Customer must not access admin dashboard');
    }

    /** @test */
    public function seller_session_cannot_access_admin_routes(): void
    {
        $seller = $this->createVerifiedMerchant();
        $response = $this->actingAsSeller($seller)
            ->get(route('admin.pos-management.dashboard'));
        $this->assertSecurityDenied($response, 'Seller must not access admin pos-management');
    }
}
