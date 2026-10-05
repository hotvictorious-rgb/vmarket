<?php

namespace Tests\Feature;

require_once __DIR__ . '/GatewayMoneyTestCase.php';

use App\Models\Product;
use App\Models\Review;
use App\Models\Seller;
use App\Models\VendorRole;
use App\Models\VendorEmployee;
use App\Models\DeliveryMan;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * [AI] Deterministic Regression Test Suite verifying fixes for all 17 external audit findings:
 * - Token and KYC serialization concealment
 * - Tenant product and review IDOR protection
 * - Internal vendor catalog route authorization
 * - Rider assignment scoping
 * - Employee default-deny permission enforcement
 */
class SecurityAuditRegressionTest extends GatewayMoneyTestCase
{
    private function fixture(string $table, array $values): int
    {
        $d = [];
        foreach (DB::select('PRAGMA table_info("' . $table . '")') as $c) {
            if ($c->name !== 'id' && $c->notnull && $c->dflt_value === null) {
                $d[$c->name] = preg_match('/INT|DECIMAL|DOUBLE|FLOAT|REAL|NUMERIC/i', $c->type) ? 0 : '';
            }
        }
        return DB::table($table)->insertGetId($values + $d);
    }

    public function test_seller_serialization_never_exposes_tokens_banking_or_kyc_data(): void
    {
        $token = str_repeat('s', 50);
        $sid = $this->fixture('sellers', [
            'f_name' => 'John',
            'l_name' => 'Vendor',
            'email' => 'john@vendor.test',
            'phone' => '08011223344',
            'status' => 'approved',
            'auth_token' => hash('sha256', $token),
            'bank_name' => 'Secret Bank',
            'account_no' => '1234567890',
            'nin' => '98765432101',
            'cac_number' => 'RC-998877',
        ]);

        $seller = Seller::find($sid);
        $json = $seller->toArray();

        $this->assertArrayNotHasKey('auth_token', $json);
        $this->assertArrayNotHasKey('bank_name', $json);
        $this->assertArrayNotHasKey('account_no', $json);
        $this->assertArrayNotHasKey('nin', $json);
        $this->assertArrayNotHasKey('cac_number', $json);
        $this->assertArrayNotHasKey('password', $json);
    }

    public function test_cross_vendor_product_idor_is_blocked(): void
    {
        $tokenA = str_repeat('a', 50);
        $tokenB = str_repeat('b', 50);
        $sidA = $this->fixture('sellers', ['status' => 'approved', 'auth_token' => hash('sha256', $tokenA)]);
        $sidB = $this->fixture('sellers', ['status' => 'approved', 'auth_token' => hash('sha256', $tokenB)]);

        $pidB = $this->fixture('products', [
            'added_by' => 'seller',
            'user_id' => $sidB,
            'name' => 'Vendor B Product',
            'code' => 'PROD-B-001',
            'images' => json_encode(['img1.jpg', 'img2.jpg']),
        ]);

        // Vendor A attempts to edit Vendor B's product
        $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->getJson('/api/v3/seller/products/edit/' . $pidB)
            ->assertStatus(403);

        // Vendor A attempts to delete images from Vendor B's product
        $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->postJson('/api/v3/seller/products/delete-image', ['id' => $pidB, 'name' => 'img1.jpg'])
            ->assertStatus(403);

        // Vendor A attempts to generate barcode for Vendor B's product
        $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->postJson('/api/v3/seller/products/barcode/generate', ['id' => $pidB, 'quantity' => 10])
            ->assertStatus(403);
    }

    public function test_cross_vendor_review_idor_is_blocked(): void
    {
        $tokenA = str_repeat('a', 50);
        $tokenB = str_repeat('b', 50);
        $sidA = $this->fixture('sellers', ['status' => 'approved', 'auth_token' => hash('sha256', $tokenA)]);
        $sidB = $this->fixture('sellers', ['status' => 'approved', 'auth_token' => hash('sha256', $tokenB)]);

        $pidB = $this->fixture('products', [
            'added_by' => 'seller',
            'user_id' => $sidB,
            'name' => 'Vendor B Product',
        ]);

        $rid = $this->fixture('reviews', [
            'product_id' => $pidB,
            'customer_id' => 1,
            'comment' => 'Great product',
            'rating' => 5,
            'status' => 1,
        ]);

        // Vendor A attempts to change status of review on Vendor B's product
        $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->postJson('/api/v3/seller/shop-product-reviews-status', ['id' => $rid, 'status' => 0])
            ->assertStatus(403);

        // Vendor A attempts to reply to review on Vendor B's product
        $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->postJson('/api/v3/seller/shop-product-reviews-reply', ['review_id' => $rid, 'reply_text' => 'Thanks'])
            ->assertStatus(403);
    }

    public function test_vendor_inventory_routes_require_authentication_and_correct_tenant(): void
    {
        $tokenA = str_repeat('a', 50);
        $sidA = $this->fixture('sellers', ['status' => 'approved', 'auth_token' => hash('sha256', $tokenA)]);
        $sidB = $this->fixture('sellers', ['status' => 'approved', 'auth_token' => hash('sha256', str_repeat('b', 50))]);

        // Unauthenticated access must fail with 401
        $this->getJson("/api/v3/seller/products/{$sidB}/all-products")->assertStatus(401);

        // Authenticated Vendor A attempting to access Vendor B's all-products must fail with 403
        $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->getJson("/api/v3/seller/products/{$sidB}/all-products")
            ->assertStatus(403);
    }

    public function test_vendor_cannot_assign_another_vendors_exclusive_delivery_man(): void
    {
        $tokenA = str_repeat('a', 50);
        $sidA = $this->fixture('sellers', ['status' => 'approved', 'auth_token' => hash('sha256', $tokenA)]);
        $sidB = $this->fixture('sellers', ['status' => 'approved', 'auth_token' => hash('sha256', str_repeat('b', 50))]);

        $orderA = $this->fixture('orders', [
            'seller_id' => $sidA,
            'order_status' => 'confirmed',
            'order_type' => 'default_type',
        ]);

        $riderB = $this->fixture('delivery_men', [
            'seller_id' => $sidB, // exclusive to vendor B
            'is_active' => 1,
            'f_name' => 'Rider',
            'l_name' => 'B',
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->postJson('/api/v3/seller/orders/assign-delivery-man', [
                'order_id' => $orderA,
                'delivery_man_id' => $riderB,
            ])
            ->assertStatus(403);
    }

    public function test_employee_default_deny_blocks_unauthorized_modules_and_owner_enclaves(): void
    {
        $token = str_repeat('e', 50);
        $sid = $this->fixture('sellers', ['status' => 'approved', 'auth_token' => hash('sha256', str_repeat('o', 50))]);
        $roleId = $this->fixture('vendor_roles', ['seller_id' => $sid, 'name' => 'Order Staff', 'module_access' => json_encode(['order']), 'status' => 1]);
        $this->fixture('vendor_employees', [
            'seller_id' => $sid,
            'vendor_role_id' => $roleId,
            'status' => 1,
            'auth_token' => hash('sha256', $token),
        ]);

        // Allowed module: orders
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v3/seller/orders/list')
            ->assertOk();

        // Disallowed module: products (employee only has 'order')
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v3/seller/products/list')
            ->assertStatus(403);

        // Owner-only enclave: banking/withdraw
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v3/seller/withdraw/list')
            ->assertStatus(403);
    }
}
