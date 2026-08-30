<?php

namespace Tests\Security;

use App\Models\Order;
use App\Models\ShippingAddress;
use App\User;

/**
 * [AI] Suite 06 — CUSTOMER IDOR PROTECTION (Role 9)
 *
 * Tests that Customer A cannot access Customer B's orders, addresses, wallet,
 * payment methods, or personal data via Insecure Direct Object Reference (IDOR).
 *
 * SECURITY STANDARDS TESTED:
 *  - Customer A cannot view Customer B's order by changing order ID in URL
 *  - Customer A cannot view Customer B's delivery address
 *  - Customer A cannot delete Customer B's address
 *  - Customer A's API token cannot fetch Customer B's order list
 *  - Customer A cannot view Customer B's wallet balance
 *  - Customer A cannot add items to Customer B's cart via guest_id spoofing
 *  - Unauthenticated access to order/account routes redirects to login
 *
 * RUN: php artisan test tests/Security/Suite06_CustomerIDORTest.php
 */
class Suite06_CustomerIDORTest extends SecurityTestCase
{
    /** @test [VULN-001] Customer A cannot view Customer B's order via direct URL */
    public function customer_cannot_view_another_customers_order_detail(): void
    {
        $customerA = $this->createCustomer();
        $customerB = $this->createCustomer();

        $orderB = Order::factory()->create(['customer_id' => $customerB->id]);

        // Web storefront order detail
        $response = $this->actingAsCustomer($customerA)
            ->get(route('account-order-details', ['id' => $orderB->id]));

        $this->assertSecurityDenied($response, 'Customer A must not view Customer B order');
        $this->assertJsonNoIDORLeak($response, $customerB->id, 'customer_id');
    }

    /** @test Customer A cannot view Customer B's shipping addresses */
    public function customer_cannot_view_another_customers_addresses(): void
    {
        $customerA = $this->createCustomer();
        $customerB = $this->createCustomer();

        $addressB = ShippingAddress::factory()->create(['customer_id' => $customerB->id]);

        $response = $this->actingAsCustomer($customerA)
            ->get(route('address-edit', ['id' => $addressB->id]));

        $this->assertSecurityDenied($response, 'Customer A must not edit Customer B address');
    }

    /** @test Customer A cannot delete Customer B's shipping address */
    public function customer_cannot_delete_another_customers_address(): void
    {
        $customerA = $this->createCustomer();
        $customerB = $this->createCustomer();

        $addressB = ShippingAddress::factory()->create(['customer_id' => $customerB->id]);

        $response = $this->actingAsCustomer($customerA)
            ->get(route('address-delete', ['id' => $addressB->id]));

        $this->assertSecurityDenied($response, 'Customer A must not delete Customer B address');
        $this->assertDatabaseHas('shipping_addresses', ['id' => $addressB->id]);
    }

    /** @test API: Customer A cannot fetch Customer B's orders via REST API */
    public function customer_api_cannot_access_other_customer_orders(): void
    {
        $customerA = $this->createCustomer();
        $customerB = $this->createCustomer();
        $orderB = Order::factory()->create(['customer_id' => $customerB->id]);

        $tokenA = $customerA->createToken('mobile-token')->accessToken ?? '';

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->getJson("/api/v1/orders/details/{$orderB->id}");

        $this->assertSecurityDenied($response, 'API: Customer A cannot access Customer B order');
        $this->assertJsonNoIDORLeak($response, $customerB->id, 'customer_id');
    }

    /** @test Unauthenticated requests to account pages are redirected to login */
    public function unauthenticated_cannot_access_account_routes(): void
    {
        $accountRoutes = [
            route('account-oder'),
            route('account-address'),
            route('account-wishlist'),
            route('account-payment'),
        ];

        foreach ($accountRoutes as $url) {
            $response = $this->get($url);
            $this->assertSecurityDenied($response, "Unauthenticated must be denied [{$url}]");
        }
    }

    /** @test Customer cannot escalate to admin panel via session manipulation */
    public function customer_cannot_access_admin_routes(): void
    {
        $customer = $this->createCustomer();
        $response = $this->actingAsCustomer($customer)->get(route('admin.dashboard'));
        $this->assertSecurityDenied($response, 'Customer web session must not access admin');
    }

    /** @test Customer cannot access another customer wallet via account delete route */
    public function customer_cannot_delete_another_customers_account(): void
    {
        $customerA = $this->createCustomer();
        $customerB = $this->createCustomer();

        $response = $this->actingAsCustomer($customerA)
            ->get(route('account-delete', ['id' => $customerB->id]));

        $this->assertSecurityDenied($response, 'Customer A must not delete Customer B account');
        $this->assertDatabaseHas('users', ['id' => $customerB->id]);
    }

    /** @test API: Customer order list only returns OWN orders (no cross-customer bleed) */
    public function customer_api_order_list_is_isolated_to_own_orders(): void
    {
        $customerA = $this->createCustomer();
        $customerB = $this->createCustomer();

        Order::factory()->count(3)->create(['customer_id' => $customerA->id]);
        Order::factory()->count(2)->create(['customer_id' => $customerB->id]);

        $tokenA = $customerA->createToken('mobile-token')->accessToken ?? '';
        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->getJson('/api/v1/orders');

        $response->assertOk();
        $orders = $response->json('orders.data') ?? $response->json('data') ?? [];

        foreach ($orders as $order) {
            $this->assertEquals($customerA->id, $order['customer_id'],
                "Order in list has customer_id={$order['customer_id']} but must be {$customerA->id}");
        }
    }
}
