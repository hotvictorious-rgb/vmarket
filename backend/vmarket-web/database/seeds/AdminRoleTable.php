<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminRoleTable extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            [
                'id' => 1,
                'name' => 'Master Admin',
                'module_access' => null,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Central Logistics & Dispatch Officer',
                'module_access' => json_encode(['dashboard', 'order_management', 'delivery_management', 'dispatch_portal']),
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Product & Pricing Gateway Approver',
                'module_access' => json_encode(['dashboard', 'product_management', 'approval_portal', 'category_management']),
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'Customer Care & Support Specialist',
                'module_access' => json_encode(['dashboard', 'support_section', 'user_section', 'customer_management']),
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($roles as $role) {
            DB::table('admin_roles')->updateOrInsert(['id' => $role['id']], $role);
        }
    }
}
