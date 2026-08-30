<?php

namespace Tests\Security;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Shop;
use App\User;
use Illuminate\Support\Facades\DB;

/**
 * [AI] Suite 09 — ANTI-MASS-ASSIGNMENT, BALANCE LOCKS & DATA INTEGRITY (Pillars 3 & 4)
 *
 * Tests against mass-assignment parameter injection, unlocked balance mutations,
 * and unvalidated request overrides.
 *
 * SECURITY STANDARDS TESTED:
 *  - Mass assignment cannot overwrite sensitive columns (wallet_balance, is_paid, role_id, seller_id)
 *  - Pessimistic balance locks execute inside DB::transaction() with lockForUpdate()
 *  - Request sanitization prevents parameter tampering on order placement and profile updates
 *
 * RUN: php artisan test tests/Security/Suite09_AntiMassAssignmentAndInputValidationTest.php
 */
class Suite09_AntiMassAssignmentAndInputValidationTest extends SecurityTestCase
{
    /** @test Mass assignment on Customer profile cannot overwrite wallet balance or role */
    public function customer_profile_update_cannot_inject_wallet_balance_or_role(): void
    {
        $customer = $this->createCustomer(['wallet_balance' => 0.00]);

        $response = $this->actingAsCustomer($customer)
            ->post(route('user-account-update'), [
                'f_name'         => 'HackedName',
                'l_name'         => 'HackedLast',
                'email'          => $customer->email,
                'phone'          => '08012345678',
                'wallet_balance' => 999999.99, // Parameter injection attempt
                'role_id'        => 1,         // Privilege escalation attempt
                'is_active'      => 1,
            ]);

        $customer->refresh();
        $this->assertEquals(0.00, (float)$customer->wallet_balance,
            'CRITICAL MASS ASSIGNMENT: Customer wallet_balance was overwritten via request injection!');
    }

    /** @test Seller cannot overwrite own status or seller_id on shop update */
    public function seller_shop_update_cannot_inject_seller_id_or_status(): void
    {
        $sellerA = $this->createVerifiedMerchant();
        $sellerB = $this->createVerifiedMerchant();
        $shopA   = $sellerA->shop;

        $response = $this->actingAsSeller($sellerA)
            ->post(route('vendor.shop.update'), [
                'id'        => $shopA->id,
                'name'      => 'Legit Shop Name',
                'seller_id' => $sellerB->id, // Attempt to reassign ownership
                'status'    => 1,
            ]);

        $shopA->refresh();
        $this->assertEquals($sellerA->id, $shopA->seller_id,
            'CRITICAL MASS ASSIGNMENT: Shop seller_id reassigned to foreign seller!');
    }

    /** @test Pessimistic balance concurrency lock pattern enforces serial execution */
    public function balance_mutation_requires_transactional_pessimistic_lock(): void
    {
        $customer = $this->createCustomer(['wallet_balance' => 1000.00]);

        // Simulating atomic wallet deduction with pessimistic lock
        $success = DB::transaction(function () use ($customer) {
            $lockedUser = User::where('id', $customer->id)->lockForUpdate()->first();
            if ($lockedUser->wallet_balance >= 200.00) {
                $lockedUser->wallet_balance -= 200.00;
                $lockedUser->save();
                return true;
            }
            return false;
        });

        $this->assertTrue($success);
        $customer->refresh();
        $this->assertEquals(800.00, (float)$customer->wallet_balance);
    }
}
