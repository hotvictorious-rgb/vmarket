<?php

namespace Tests\Feature;

require_once __DIR__ . '/DumpSchemaTestCase.php';

use App\Models\PickupReservation;
use App\Models\Seller;
use App\Models\Shop;
use App\Models\User;
use App\Services\PickupReservationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TransitHandshakeTest extends DumpSchemaTestCase
{
    protected PickupReservationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Ensure pickup_reservations table exists in memory sqlite
        if (!Schema::hasTable('pickup_reservations')) {
            (require base_path('database/migrations/2026_09_19_000002_create_pickup_reservations_table.php'))->up();
        }
        (require base_path('database/migrations/2026_10_14_000001_add_en_route_and_holding_to_pickup_reservations.php'))->up();

        if (!Schema::hasColumn('shops', 'pickup_opening_time')) {
            Schema::table('shops', function ($t) {
                $t->boolean('pickup_enabled')->default(1);
                $t->string('pickup_opening_time')->nullable();
                $t->string('pickup_closing_time')->nullable();
            });
        }

        // Configure default admin settings
        foreach ([
            'pickup_inspection_window_hours' => 24,
            'pickup_en_route_hold_minutes' => 90,
            'pickup_vendor_response_sla_minutes' => 15,
            'pickup_max_active_en_route_per_customer' => 1,
            'pickup_en_route_resend_cooldown_minutes' => 5,
        ] as $k => $v) {
            DB::table('business_settings')->updateOrInsert(['type' => $k], ['value' => (string) $v]);
        }
        Cache::flush();

        $this->service = app(PickupReservationService::class);
    }

    protected function createTestContext(array $shopAttributes = []): array
    {
        $sellerId = DB::table('sellers')->insertGetId([
            'f_name' => 'Emeka',
            'l_name' => 'Trader',
            'phone' => '080' . rand(10000000, 99999999),
            'email' => 'emeka_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $shopId = DB::table('shops')->insertGetId(array_merge([
            'seller_id' => $sellerId,
            'name' => 'Emeka Gadgets Plaza',
            'address' => 'Shop B12, Victorious Plaza, Uyo',
            'contact' => '08012345678',
            'image' => 'def.png',
            'banner' => 'def.png',
            'pickup_enabled' => 1,
            'pickup_opening_time' => '08:00',
            'pickup_closing_time' => '20:00',
            'created_at' => now(),
            'updated_at' => now(),
        ], $shopAttributes));

        $customerId = DB::table('users')->insertGetId([
            'f_name' => 'Chidi',
            'l_name' => 'Buyer',
            'phone' => '080' . rand(10000000, 99999999),
            'email' => 'chidi_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $seller = Seller::find($sellerId);
        $shop = Shop::find($shopId);
        $customer = User::find($customerId);

        $code = 'RES-' . strtoupper(bin2hex(random_bytes(4)));
        $reservation = PickupReservation::create([
            'reservation_code' => $code,
            'idempotency_key' => 'IDEM_' . uniqid(),
            'customer_id' => $customerId,
            'seller_id' => $sellerId,
            'shop_id' => $shopId,
            'reservation_fingerprint' => hash('sha256', $code),
            'status' => 'pending_inspection',
            'total_amount' => '45000.00',
            'currency' => 'NGN',
            'reservation_items' => [
                'shop' => [
                    'shop_id' => $shopId,
                    'name' => $shop->name,
                    'address' => $shop->address,
                ],
                'items' => [
                    ['product_name' => 'Smart Phone X', 'quantity' => 1, 'unit_price' => '45000.00'],
                ],
            ],
            'expires_at' => Carbon::now()->addHours(24),
        ]);

        return [$seller, $shop, $customer, $reservation];
    }

    public function test_customer_signals_en_route_successfully(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14 11:00:00'));

        [$seller, $shop, $customer, $reservation] = $this->createTestContext([
            'pickup_opening_time' => '08:00',
            'pickup_closing_time' => '18:00',
        ]);

        $result = $this->service->customerSignalEnRoute($customer->id, $reservation->reservation_code);

        $this->assertSame('SUCCESS', $result['status']);
        $this->assertEquals(15, $result['sla_minutes']);

        $reservation->refresh();
        $this->assertSame('en_route', $reservation->status);
        $this->assertNotNull($reservation->en_route_at);
        $this->assertNotNull($reservation->en_route_sla_expires_at);
        $this->assertEquals(1, $reservation->en_route_resend_count);
    }

    public function test_customer_cannot_signal_en_route_when_shop_closed(): void
    {
        // 9:30 PM (Shop closes at 6:00 PM)
        Carbon::setTestNow(Carbon::parse('2026-10-14 21:30:00'));

        [$seller, $shop, $customer, $reservation] = $this->createTestContext([
            'pickup_opening_time' => '08:00',
            'pickup_closing_time' => '18:00',
        ]);

        $result = $this->service->customerSignalEnRoute($customer->id, $reservation->reservation_code);

        $this->assertSame('SHOP_CLOSED', $result['status']);
        $reservation->refresh();
        $this->assertSame('pending_inspection', $reservation->status);
    }

    public function test_customer_cannot_signal_within_90_minutes_of_closing(): void
    {
        // 5:15 PM (Shop closes at 6:00 PM -> only 45 mins left)
        Carbon::setTestNow(Carbon::parse('2026-10-14 17:15:00'));

        [$seller, $shop, $customer, $reservation] = $this->createTestContext([
            'pickup_opening_time' => '08:00',
            'pickup_closing_time' => '18:00',
        ]);

        $result = $this->service->customerSignalEnRoute($customer->id, $reservation->reservation_code);

        $this->assertSame('SHOP_CLOSING_SOON', $result['status']);
        $reservation->refresh();
        $this->assertSame('pending_inspection', $reservation->status);
    }

    public function test_vendor_confirms_physical_hold_locks_stock_for_90_minutes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14 12:00:00'));

        [$seller, $shop, $customer, $reservation] = $this->createTestContext();

        $this->service->customerSignalEnRoute($customer->id, $reservation->reservation_code);

        $holdResult = $this->service->vendorConfirmPhysicalHold($seller->id, $reservation->reservation_code, $shop->id);

        $this->assertSame('SUCCESS', $holdResult['status']);
        $this->assertEquals(90, $holdResult['hold_minutes']);

        $reservation->refresh();
        $this->assertSame('held_confirmed', $reservation->status);
        $this->assertNotNull($reservation->held_at);
        $this->assertNotNull($reservation->held_until);
        $this->assertTrue($reservation->held_until->equalTo(Carbon::parse('2026-10-14 13:30:00')));
    }

    public function test_vendor_sla_timeout_allows_customer_resend_after_cooldown(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14 12:00:00'));

        [$seller, $shop, $customer, $reservation] = $this->createTestContext();

        $this->service->customerSignalEnRoute($customer->id, $reservation->reservation_code);

        // Advance time by 16 minutes (SLA expires)
        Carbon::setTestNow(Carbon::parse('2026-10-14 12:16:00'));

        // Lazy expiry triggers on model
        $reservation->refresh();
        $this->assertFalse($reservation->checkAndApplyLazyExpiry());
        $this->assertSame('en_route_timeout', $reservation->status);

        // Attempting resend immediately within cooldown should be blocked
        $reservation->last_en_route_ping_at = Carbon::parse('2026-10-14 12:14:00'); // only 2 mins ago
        $reservation->save();
        $resendBlocked = $this->service->customerSignalEnRoute($customer->id, $reservation->reservation_code);
        $this->assertSame('COOLDOWN_ACTIVE', $resendBlocked['status']);

        // After 6 minutes have elapsed, resend succeeds
        $reservation->last_en_route_ping_at = Carbon::parse('2026-10-14 12:09:00'); // 7 mins ago
        $reservation->save();
        $resendSuccess = $this->service->customerSignalEnRoute($customer->id, $reservation->reservation_code);
        $this->assertSame('SUCCESS', $resendSuccess['status']);

        $reservation->refresh();
        $this->assertSame('en_route', $reservation->status);
        $this->assertEquals(2, $reservation->en_route_resend_count);
    }

    public function test_vendor_reports_out_of_stock_prevents_wasted_trip(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14 12:00:00'));

        [$seller, $shop, $customer, $reservation] = $this->createTestContext();

        $this->service->customerSignalEnRoute($customer->id, $reservation->reservation_code);

        $outResult = $this->service->vendorReportOutOfStock($seller->id, $reservation->reservation_code, $shop->id, 'Sold out to walk-in customer');

        $this->assertSame('SUCCESS', $outResult['status']);

        $reservation->refresh();
        $this->assertSame('inspected_rejected', $reservation->status);
        $this->assertSame('Sold out to walk-in customer', $reservation->rejection_reason);
    }

    public function test_customer_cancels_transit_trip_frees_vendor_hold(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14 12:00:00'));

        [$seller, $shop, $customer, $reservation] = $this->createTestContext();

        $this->service->customerSignalEnRoute($customer->id, $reservation->reservation_code);
        $this->service->vendorConfirmPhysicalHold($seller->id, $reservation->reservation_code, $shop->id);

        $reservation->refresh();
        $this->assertSame('held_confirmed', $reservation->status);

        // Customer cancels transit
        $cancelResult = $this->service->customerCancelEnRoute($customer->id, $reservation->reservation_code);

        $this->assertSame('SUCCESS', $cancelResult['status']);

        $reservation->refresh();
        $this->assertSame('pending_inspection', $reservation->status);
        $this->assertNull($reservation->held_until);
    }
}
