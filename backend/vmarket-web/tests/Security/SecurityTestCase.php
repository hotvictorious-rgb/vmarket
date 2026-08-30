<?php

namespace Tests\Security;

use App\Models\Admin;
use App\Models\AdminRole;
use App\Models\Customer;
use App\Models\DeliveryMan;
use App\Models\Order;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Shop;
use App\Models\VendorEmployee;
use App\Models\VendorRole;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\CreatesApplication;

/**
 * [AI] SecurityTestCase - Base class for all 10 Vmarket Security Test Suites.
 * Provides factory helpers for all 9 ecosystem roles from AGENTS.md Section 11.
 *
 * RUN ALL SUITES: php artisan test tests/Security/ --stop-on-failure
 * RUN ONE SUITE:  php artisan test tests/Security/Suite01_SuperAdminAccessTest.php
 *
 * 9 ROLES:
 *  1. Super Admin          (admin_role_id=1, bypasses all module gates)
 *  2. Admin Employee       (restricted module_access JSON)
 *  3. Verified Merchant    (seller status=1, KYC approved)
 *  4. Unverified Merchant  (seller status=0, KYC pending)
 *  5. Verified Merchant Employee (VendorEmployee on active shop)
 *  6. Unverified Merchant Employee (VendorEmployee on free-tier shop)
 *  7. Active Deliveryman   (application_status=approved, status=1)
 *  8. Inactive Deliveryman (application_status=pending, status=0)
 *  9. Customer             (User, active account)
 */
abstract class SecurityTestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase, WithFaker;

    // =========================================================================
    // ROLE FACTORY HELPERS
    // =========================================================================

    protected function createSuperAdmin(array $overrides = []): Admin
    {
        return Admin::factory()->create(array_merge([
            'name'          => 'Super Admin',
            'email'         => 'superadmin_' . uniqid() . '@vmarket.test',
            'password'      => Hash::make('SecurePass123!'),
            'admin_role_id' => 1,
            'status'        => 1,
        ], $overrides));
    }

    protected function createAdminEmployee(array $moduleAccess = ['order_management']): Admin
    {
        $role = AdminRole::factory()->create([
            'name'          => 'Employee Role ' . uniqid(),
            'module_access' => json_encode($moduleAccess),
            'status'        => 1,
        ]);
        return Admin::factory()->create([
            'name'          => 'Admin Employee',
            'email'         => 'employee_' . uniqid() . '@vmarket.test',
            'password'      => Hash::make('SecurePass123!'),
            'admin_role_id' => $role->id,
            'status'        => 1,
        ]);
    }

    protected function createVerifiedMerchant(array $overrides = []): Seller
    {
        $seller = Seller::factory()->create(array_merge([
            'email'    => 'merchant_' . uniqid() . '@vmarket.test',
            'password' => Hash::make('SecurePass123!'),
            'status'   => 1,
        ], $overrides));
        Shop::factory()->create(['seller_id' => $seller->id, 'status' => 1]);
        return $seller->fresh();
    }

    protected function createUnverifiedMerchant(array $overrides = []): Seller
    {
        $seller = Seller::factory()->create(array_merge([
            'email'    => 'unverified_' . uniqid() . '@vmarket.test',
            'password' => Hash::make('SecurePass123!'),
            'status'   => 0,
        ], $overrides));
        Shop::factory()->create(['seller_id' => $seller->id, 'status' => 0]);
        return $seller->fresh();
    }

    protected function createActiveDeliveryMan(array $overrides = []): DeliveryMan
    {
        return DeliveryMan::factory()->create(array_merge([
            'email'              => 'rider_' . uniqid() . '@vmarket.test',
            'password'           => Hash::make('SecurePass123!'),
            'application_status' => 'approved',
            'status'             => 1,
        ], $overrides));
    }

    protected function createInactiveDeliveryMan(array $overrides = []): DeliveryMan
    {
        return DeliveryMan::factory()->create(array_merge([
            'email'              => 'rider2_' . uniqid() . '@vmarket.test',
            'password'           => Hash::make('SecurePass123!'),
            'application_status' => 'pending',
            'status'             => 0,
        ], $overrides));
    }

    protected function createCustomer(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'name'     => 'Customer ' . uniqid(),
            'email'    => 'customer_' . uniqid() . '@vmarket.test',
            'password' => Hash::make('SecurePass123!'),
            'status'   => 1,
        ], $overrides));
    }

    // =========================================================================
    // AUTH HELPERS
    // =========================================================================

    protected function actingAsAdmin(Admin $admin): static
    {
        return $this->actingAs($admin, 'admin');
    }

    protected function actingAsSeller(Seller $seller): static
    {
        return $this->actingAs($seller, 'seller');
    }

    protected function actingAsCustomer(User $user): static
    {
        return $this->actingAs($user, 'web');
    }

    protected function actingAsDeliveryManAPI(DeliveryMan $rider): static
    {
        $token = $rider->createToken('test-token')->accessToken ?? '';
        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    // =========================================================================
    // ASSERTION HELPERS
    // =========================================================================

    protected function assertSecurityDenied($response, string $hint = ''): void
    {
        $status = $response->getStatusCode();
        $this->assertContains(
            $status, [401, 403, 302],
            "SECURITY BREACH: Expected denial (401/403/302) but got {$status}. {$hint}"
        );
    }

    protected function assertNotHttp200($response, string $protectedRoute = ''): void
    {
        $this->assertNotEquals(200, $response->getStatusCode(),
            "SECURITY BREACH: Unauthorized access to [{$protectedRoute}] returned HTTP 200."
        );
    }

    protected function assertJsonNoIDORLeak($response, int $foreignId, string $field = 'customer_id'): void
    {
        $body = json_encode($response->json());
        $pattern = '"' . $field . '":' . $foreignId;
        $this->assertStringNotContainsString(
            $pattern, $body,
            "IDOR LEAK: Response contains {$field}={$foreignId} belonging to another user."
        );
    }
}
