<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [AI] Migration: Pay-on-Delivery (POD) Upfront Dispatch Fee & Free Delivery Prepaid Restrictions
     */
    public function up(): void
    {
        // 1. Add order breakdown columns to orders table
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'pod_dispatch_fee')) {
                    $table->decimal('pod_dispatch_fee', 14, 2)->default(0.00)->after('order_amount');
                }
                if (!Schema::hasColumn('orders', 'doorstep_due_amount')) {
                    $table->decimal('doorstep_due_amount', 14, 2)->default(0.00)->after('pod_dispatch_fee');
                }
            });
        }

        // 2. Seed business settings defaults
        $settings = [
            ['type' => 'pod_dispatch_fee_status', 'value' => '1'],
            ['type' => 'pod_dispatch_fee_amount', 'value' => '1000.00'],
            ['type' => 'pod_free_delivery_prepaid_only', 'value' => '1'],
        ];

        foreach ($settings as $setting) {
            $exists = DB::table('business_settings')->where('type', $setting['type'])->exists();
            if (!$exists) {
                DB::table('business_settings')->insert([
                    'type' => $setting['type'],
                    'value' => $setting['value'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'pod_dispatch_fee')) {
                    $table->dropColumn('pod_dispatch_fee');
                }
                if (Schema::hasColumn('orders', 'doorstep_due_amount')) {
                    $table->dropColumn('doorstep_due_amount');
                }
            });
        }

        DB::table('business_settings')->whereIn('type', [
            'pod_dispatch_fee_status',
            'pod_dispatch_fee_amount',
            'pod_free_delivery_prepaid_only'
        ])->delete();
    }
};
