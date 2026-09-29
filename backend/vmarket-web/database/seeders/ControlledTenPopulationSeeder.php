<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ControlledTenPopulationSeeder extends Seeder
{
    public function run()
    {
        $defaultPassword = Hash::make('12345678');
        $now = now();

        echo "[*] Seeding 10 Customers with Uyo Addresses & Wallets...\n";
        $streets = [
            '14 Ikot Ekpene Road, Uyo',
            '88 Oron Road, Uyo',
            'Plaza Circus, Shop 5, Uyo',
            '22 Nwaniba Road, Uyo',
            '105 Aka Road, Uyo',
            '45 Abak Road, Uyo',
            '12 Wellington Bassey Way, Uyo',
            '67 Udoumana Street, Uyo',
            '90 Stadium Road, Uyo',
            '15 Banking District, Commercial Avenue, Uyo'
        ];

        for ($i = 1; $i <= 10; $i++) {
            $num = str_pad($i, 2, '0', STR_PAD_LEFT);
            $email = "cust{$num}@vmarket.test";
            $phone = "080200000{$num}";
            $name = "Customer {$num}";

            $user = DB::table('users')->where('email', $email)->first();
            if (!$user) {
                $userId = DB::table('users')->insertGetId([
                    'name' => $name,
                    'f_name' => 'Customer',
                    'l_name' => $num,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => $defaultPassword,
                    'is_active' => 1,
                    'is_phone_verified' => 1,
                    'is_email_verified' => 1,
                    'wallet_balance' => 50000.00,
                    'loyalty_point' => 100,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $userId = $user->id;
                DB::table('users')->where('id', $userId)->update([
                    'password' => $defaultPassword,
                    'is_active' => 1,
                    'wallet_balance' => 50000.00,
                ]);
            }

            // Customer Wallet
            $wallet = DB::table('customer_wallets')->where('customer_id', $userId)->first();
            if (!$wallet) {
                DB::table('customer_wallets')->insert([
                    'customer_id' => $userId,
                    'balance' => 50000.00,
                    'royality_points' => 100,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Customer Saved Address (Uyo LGA = 69, Akwa Ibom = 3, Nigeria = 1)
            $addr = DB::table('shipping_addresses')->where('customer_id', $userId)->first();
            if (!$addr) {
                DB::table('shipping_addresses')->insert([
                    'customer_id' => $userId,
                    'is_guest' => 0,
                    'contact_person_name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'address_type' => 'home',
                    'address' => $streets[$i - 1],
                    'city' => 'Uyo',
                    'zip' => '520001',
                    'state' => 'Akwa Ibom',
                    'country' => 'Nigeria',
                    'country_id' => 1,
                    'state_id' => 3,
                    'lga_id' => 69,
                    'is_billing' => 0,
                    'latitude' => 5.0377 + ($i * 0.001),
                    'longitude' => 7.9128 + ($i * 0.001),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        echo "[*] Seeding 10 Vendors with 20 Uyo Pickup Points & Wallets...\n";
        $vendorNames = [
            'ABC Electronics', 'Prime Gadgets Uyo', 'Akwa Boutique', 'Royal Mobile', 'Apex Home Needs',
            'Sunrise Superstore', 'Metro Footwear', 'Empire Provisions', 'Galaxy Tech', 'Uyo Essentials'
        ];

        for ($v = 1; $v <= 10; $v++) {
            $vnum = str_pad($v, 2, '0', STR_PAD_LEFT);
            $vemail = "vendor{$vnum}@vmarket.test";
            $vphone = "080100000{$vnum}";
            $vtitle = $vendorNames[$v - 1];

            $seller = DB::table('sellers')->where('email', $vemail)->first();
            if (!$seller) {
                $sellerId = DB::table('sellers')->insertGetId([
                    'f_name' => 'Vendor',
                    'l_name' => $vnum,
                    'phone' => $vphone,
                    'email' => $vemail,
                    'password' => $defaultPassword,
                    'status' => 'approved',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $sellerId = $seller->id;
                DB::table('sellers')->where('id', $sellerId)->update([
                    'password' => $defaultPassword,
                    'status' => 'approved',
                ]);
            }

            // Seller Wallet
            $swallet = DB::table('seller_wallets')->where('seller_id', $sellerId)->first();
            if (!$swallet) {
                DB::table('seller_wallets')->insert([
                    'seller_id' => $sellerId,
                    'total_earning' => 0,
                    'withdrawn' => 0,
                    'commission_given' => 0,
                    'pending_withdraw' => 0,
                    'delivery_charge_earned' => 0,
                    'collected_cash' => 0,
                    'total_tax_collected' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Primary Shop (Pickup Point 1)
            $shopSlug1 = Str::slug($vtitle . ' Main ' . $sellerId);
            $shop1 = DB::table('shops')->where('seller_id', $sellerId)->where('is_primary_branch', 1)->first();
            if (!$shop1) {
                $shopId1 = DB::table('shops')->insertGetId([
                    'seller_id' => $sellerId,
                    'name' => $vtitle . ' (Main Shop)',
                    'slug' => $shopSlug1,
                    'address' => "No. " . (10 + $v) . " Ikot Ekpene Road, Uyo",
                    'contact' => $vphone,
                    'image' => 'def.png',
                    'banner' => 'def.png',
                    'bottom_banner' => 'def.png',
                    'offer_banner' => 'def.png',
                    'is_primary_branch' => 1,
                    'branch_code' => "BR-{$vnum}-01",
                    'country_id' => 1,
                    'state_id' => 3,
                    'lga_id' => 69,
                    'pickup_enabled' => 1,
                    'pickup_opening_time' => '08:00:00',
                    'pickup_closing_time' => '18:00:00',
                    'pickup_preparation_time_minutes' => 30,
                    'pickup_instructions' => 'Bring reservation code. Free customer parking available.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $shopId1 = $shop1->id;
                DB::table('shops')->where('id', $shopId1)->update([
                    'country_id' => 1,
                    'state_id' => 3,
                    'lga_id' => 69,
                    'pickup_enabled' => 1,
                ]);
            }

            // Secondary Pickup Point (Pickup Point 2)
            $shopSlug2 = Str::slug($vtitle . ' Plaza Annex ' . $sellerId);
            $shop2 = DB::table('shops')->where('seller_id', $sellerId)->where('is_primary_branch', 0)->first();
            if (!$shop2) {
                DB::table('shops')->insert([
                    'seller_id' => $sellerId,
                    'name' => $vtitle . ' (Plaza Annex)',
                    'slug' => $shopSlug2,
                    'address' => "Plaza Mall, Suite " . (100 + $v) . ", Uyo",
                    'contact' => $vphone,
                    'image' => 'def.png',
                    'banner' => 'def.png',
                    'bottom_banner' => 'def.png',
                    'offer_banner' => 'def.png',
                    'is_primary_branch' => 0,
                    'branch_code' => "BR-{$vnum}-02",
                    'country_id' => 1,
                    'state_id' => 3,
                    'lga_id' => 69,
                    'pickup_enabled' => 1,
                    'pickup_opening_time' => '09:00:00',
                    'pickup_closing_time' => '19:00:00',
                    'pickup_preparation_time_minutes' => 20,
                    'pickup_instructions' => 'Second floor next to central escalator.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Seed 10 Products for this Vendor
            for ($p = 1; $p <= 10; $p++) {
                $pnum = str_pad($p, 2, '0', STR_PAD_LEFT);
                $pname = "{$vtitle} Item {$pnum}";
                $pslug = Str::slug("{$vtitle} Item {$pnum} {$sellerId}");
                $isStock = ($p <= 8) ? 'in_stock' : 'out_of_stock';
                $price = 5000 + ($p * 1500) + ($v * 500);

                $prod = DB::table('products')->where('slug', $pslug)->first();
                if (!$prod) {
                    DB::table('products')->insert([
                        'added_by' => 'seller',
                        'user_id' => $sellerId,
                        'shop_id' => $shopId1,
                        'name' => $pname,
                        'slug' => $pslug,
                        'product_type' => 'physical',
                        'category_ids' => json_encode([['id' => '1', 'position' => 1]]),
                        'category_id' => 1,
                        'brand_id' => 1,
                        'unit' => 'pc',
                        'min_qty' => 1,
                        'refundable' => 1,
                        'images' => json_encode(['def.png']),
                        'thumbnail' => 'def.png',
                        'unit_price' => $price,
                        'purchase_price' => $price * 0.8,
                        'tax' => 0.00,
                        'tax_type' => 'percent',
                        'tax_model' => 'include',
                        'discount' => 0.00,
                        'discount_type' => 'flat',
                        'current_stock' => 50,
                        'minimum_order_qty' => 1,
                        'status' => 1,
                        'request_status' => 1,
                        'marketplace_listing_status' => 'listed',
                        'marketplace_availability' => $isStock,
                        'marketplace_status' => 'approved',
                        'marketplace_confirmed_at' => $now,
                        'availability_confirmed_at' => $now,
                        'availability_expires_at' => $now->copy()->addDays(7),
                        'shipping_cost' => 0.00,
                        'multiply_qty' => 0,
                        'code' => "SKU-{$vnum}-{$pnum}",
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    DB::table('products')->where('id', $prod->id)->update([
                        'status' => 1,
                        'request_status' => 1,
                        'marketplace_listing_status' => 'listed',
                        'marketplace_availability' => $isStock,
                        'marketplace_confirmed_at' => $now,
                        'availability_confirmed_at' => $now,
                        'availability_expires_at' => $now->copy()->addDays(7),
                    ]);
                }
            }

            // Vendor Roles & Employees (Store Manager)
            $vRole = DB::table('vendor_roles')->where('seller_id', $sellerId)->first();
            if (!$vRole) {
                $vRoleId = DB::table('vendor_roles')->insertGetId([
                    'seller_id' => $sellerId,
                    'name' => 'Store Manager',
                    'module_access' => json_encode(['orders', 'products', 'pickup_handover']),
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $vRoleId = $vRole->id;
            }

            $staffEmail = "staff{$vnum}@vmarket.test";
            $vStaff = DB::table('vendor_employees')->where('email', $staffEmail)->first();
            if (!$vStaff) {
                DB::table('vendor_employees')->insert([
                    'seller_id' => $sellerId,
                    'vendor_role_id' => $vRoleId,
                    'name' => "{$vtitle} Manager",
                    'phone' => "080500000{$vnum}",
                    'email' => $staffEmail,
                    'password' => $defaultPassword,
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        echo "[*] Seeding 10 Delivery Riders with Wallets...\n";
        for ($r = 1; $r <= 10; $r++) {
            $rnum = str_pad($r, 2, '0', STR_PAD_LEFT);
            $remail = "rider{$rnum}@vmarket.test";
            $rphone = "080300000{$rnum}";

            $dm = DB::table('delivery_men')->where('email', $remail)->first();
            if (!$dm) {
                $dmId = DB::table('delivery_men')->insertGetId([
                    'seller_id' => 0,
                    'f_name' => 'Rider',
                    'l_name' => $rnum,
                    'phone' => $rphone,
                    'email' => $remail,
                    'password' => $defaultPassword,
                    'is_active' => 1,
                    'is_online' => 1,
                    'image' => 'def.png',
                    'identity_image' => json_encode(['def.png']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $dmId = $dm->id;
                DB::table('delivery_men')->where('id', $dmId)->update([
                    'password' => $defaultPassword,
                    'is_active' => 1,
                    'is_online' => 1,
                ]);
            }

            $dmw = DB::table('deliveryman_wallets')->where('delivery_man_id', $dmId)->first();
            if (!$dmw) {
                DB::table('deliveryman_wallets')->insert([
                    'delivery_man_id' => $dmId,
                    'current_balance' => 0,
                    'cash_in_hand' => 0,
                    'pending_withdraw' => 0,
                    'total_withdraw' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        echo "[*] Seeding 10 Admin Role Employees with RBAC & 1 Super Admin...\n";
        // Super Admin (Role 1)
        $admin = DB::table('admins')->where('email', 'admin@admin.com')->first();
        if (!$admin) {
            $adminId = DB::table('admins')->insertGetId([
                'name' => 'Super Admin',
                'email' => 'admin@admin.com',
                'phone' => '08000000000',
                'admin_role_id' => 1,
                'password' => $defaultPassword,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('admin_wallets')->insert([
                'admin_id' => $adminId,
                'withdrawn' => 0,
                'commission_earned' => 0,
                'inhouse_earning' => 0,
                'delivery_charge_earned' => 0,
                'pending_amount' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $rolesData = [
            ['id' => 10, 'name' => 'Customer Support Staff', 'modules' => ['customer_management', 'support_ticket', 'refund_request']],
            ['id' => 11, 'name' => 'Logistics Dispatcher', 'modules' => ['order_management', 'delivery_man_management', 'delivery_lanes']],
            ['id' => 12, 'name' => 'Finance Treasury', 'modules' => ['transaction', 'seller_settlement', 'refund_request']],
            ['id' => 13, 'name' => 'Catalog Merchandiser', 'modules' => ['product_management', 'category', 'brand']],
            ['id' => 14, 'name' => 'Operations Manager', 'modules' => ['order_management', 'seller_management', 'system_settings']]
        ];

        foreach ($rolesData as $rd) {
            $existingRole = DB::table('admin_roles')->where('id', $rd['id'])->first();
            if (!$existingRole) {
                DB::table('admin_roles')->insert([
                    'id' => $rd['id'],
                    'name' => $rd['name'],
                    'module_access' => json_encode($rd['modules']),
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $adminEmployees = [
            ['email' => 'support01@vmarket.test', 'role_id' => 10, 'name' => 'Support Agent 01'],
            ['email' => 'support02@vmarket.test', 'role_id' => 10, 'name' => 'Support Agent 02'],
            ['email' => 'dispatch01@vmarket.test', 'role_id' => 11, 'name' => 'Dispatcher 01'],
            ['email' => 'dispatch02@vmarket.test', 'role_id' => 11, 'name' => 'Dispatcher 02'],
            ['email' => 'finance01@vmarket.test', 'role_id' => 12, 'name' => 'Finance Auditor 01'],
            ['email' => 'finance02@vmarket.test', 'role_id' => 12, 'name' => 'Finance Auditor 02'],
            ['email' => 'catalog01@vmarket.test', 'role_id' => 13, 'name' => 'Catalog Merchandiser 01'],
            ['email' => 'catalog02@vmarket.test', 'role_id' => 13, 'name' => 'Catalog Merchandiser 02'],
            ['email' => 'ops01@vmarket.test', 'role_id' => 14, 'name' => 'Operations Officer 01'],
            ['email' => 'ops02@vmarket.test', 'role_id' => 14, 'name' => 'Operations Officer 02'],
        ];

        foreach ($adminEmployees as $idx => $ae) {
            $empNum = str_pad($idx + 1, 2, '0', STR_PAD_LEFT);
            $existingEmp = DB::table('admins')->where('email', $ae['email'])->first();
            if (!$existingEmp) {
                DB::table('admins')->insert([
                    'name' => $ae['name'],
                    'email' => $ae['email'],
                    'phone' => "080400000{$empNum}",
                    'admin_role_id' => $ae['role_id'],
                    'password' => $defaultPassword,
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('admins')->where('id', $existingEmp->id)->update([
                    'password' => $defaultPassword,
                    'admin_role_id' => $ae['role_id'],
                    'status' => 1,
                ]);
            }
        }

        echo "[OK] Complete Controlled 10-Role Population Seeded Successfully!\n";
    }
}
