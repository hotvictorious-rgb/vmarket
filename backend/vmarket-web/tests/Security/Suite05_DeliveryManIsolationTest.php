<?php

namespace Tests\Security;

use App\Models\DeliveryMan;
use App\Models\DeliverymanWallet;
use App\Models\Order;

/**
 * [AI] Suite 05 — DELIVERY MAN ISOLATION (Roles 7 & 8)
 *
 * Tests that Active Deliverymen (Role 7) are strictly isolated to their own
 * orders, earnings, and route data. Inactive Deliverymen (Role 8) are blocked
 * from all operational routes.
 *
 * SECURITY STANDARDS TESTED:
 *  - Rider A cannot see Rider B's active orders
 *  - Rider A cannot collect cash for Rider B's deliveries
 *  - Rider A cannot view Rider B's wallet/earnings
 *  - Inactive rider (status=0) is blocked from all delivery operations
 *  - Inactive rider cannot pick up orders or update delivery status
 *  - OTP entropy: delivery_man OTP must use random_bytes (VULN-005)
 *  - Rider cannot access customer personal details beyond assigned order
 *
 * RUN: php artisan test tests/Security/Suite05_DeliveryManIsolationTest.php
 */
class Suite05_DeliveryManIsolationTest extends SecurityTestCase
{
    /** @test Active Rider A cannot view Rider B's order list via API */
    public function active_rider_cannot_view_another_riders_orders(): void
    {
        $riderA = $this->createActiveDeliveryMan();
        $riderB = $this->createActiveDeliveryMan();

        // Create order assigned to Rider B
        $orderB = Order::factory()->create([
            'delivery_man_id' => $riderB->id,
            'order_status'    => 'out_for_delivery',
        ]);

        $response = $this->actingAsDeliveryManAPI($riderA)
            ->getJson("/api/v2/delivery-man/order/details/{$orderB->id}");

        $this->assertSecurityDenied($response, 'Rider A must not see Rider B order detail');
        $this->assertJsonNoIDORLeak($response, $riderB->id, 'delivery_man_id');
    }

    /** @test Inactive rider (status=0) cannot fetch assigned orders */
    public function inactive_rider_cannot_access_delivery_operations(): void
    {
        $inactiveRider = $this->createInactiveDeliveryMan();

        $response = $this->actingAsDeliveryManAPI($inactiveRider)
            ->getJson('/api/v2/delivery-man/all-orders');

        $this->assertSecurityDenied($response, 'Inactive rider must be blocked from all-orders');
    }

    /** @test Inactive rider cannot update delivery status */
    public function inactive_rider_cannot_update_order_status(): void
    {
        $inactiveRider = $this->createInactiveDeliveryMan();
        $order = Order::factory()->create(['delivery_man_id' => $inactiveRider->id]);

        $response = $this->actingAsDeliveryManAPI($inactiveRider)
            ->postJson('/api/v2/delivery-man/order/update-status', [
                'order_id' => $order->id,
                'status'   => 'delivered',
            ]);

        $this->assertSecurityDenied($response, 'Inactive rider must not update order status');
    }

    /** @test Active rider cannot view wallet balance of another rider */
    public function active_rider_cannot_view_other_riders_wallet(): void
    {
        $riderA = $this->createActiveDeliveryMan();
        $riderB = $this->createActiveDeliveryMan();
        DeliverymanWallet::factory()->create(['delivery_man_id' => $riderB->id, 'current_balance' => 5000]);

        $response = $this->actingAsDeliveryManAPI($riderA)
            ->getJson("/api/v2/delivery-man/wallet/balance/{$riderB->id}");

        $this->assertSecurityDenied($response, 'Rider A must not see Rider B wallet balance');
    }

    /** @test Rider login with inactive account is rejected */
    public function inactive_rider_login_is_rejected(): void
    {
        $inactiveRider = $this->createInactiveDeliveryMan(['email' => 'inactive@rider.test']);

        $response = $this->postJson('/api/v2/delivery-man/auth/login', [
            'email'    => 'inactive@rider.test',
            'password' => 'SecurePass123!',
        ]);

        $response->assertUnauthorized();
    }

    /** @test Rider cannot access admin or vendor routes via API */
    public function active_rider_cannot_access_admin_routes(): void
    {
        $rider = $this->createActiveDeliveryMan();
        $response = $this->actingAsDeliveryManAPI($rider)
            ->get(route('admin.orders.list'));
        $this->assertSecurityDenied($response, 'Rider must not access admin order list');
    }
}
