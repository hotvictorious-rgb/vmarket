<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('order_status');
            }
            if (!Schema::hasColumn('orders', 'refund_window_expires_at')) {
                $table->timestamp('refund_window_expires_at')->nullable()->after('received_at');
            }
            if (!Schema::hasColumn('orders', 'vendor_settlement_status')) {
                // 5 normal V1 settlement states + 1 legacy manual-review sentinel
                $table->enum('vendor_settlement_status', [
                    'held',
                    'eligible',
                    'disputed',
                    'refunded',
                    'settled',
                    'legacy_hold',
                ])->nullable()->after('refund_window_expires_at');
            }
            if (!Schema::hasColumn('orders', 'rider_picked_up_at')) {
                $table->timestamp('rider_picked_up_at')->nullable()->after('vendor_settlement_status');
            }
            if (!Schema::hasColumn('orders', 'rider_picked_up_by')) {
                $table->unsignedBigInteger('rider_picked_up_by')->nullable()->after('rider_picked_up_at');
            }
            if (!Schema::hasColumn('orders', 'settled_at')) {
                $table->timestamp('settled_at')->nullable()->after('rider_picked_up_by');
            }
            if (!Schema::hasColumn('orders', 'settled_by_id')) {
                $table->unsignedBigInteger('settled_by_id')->nullable()->after('settled_at');
            }
            if (!Schema::hasColumn('orders', 'settlement_reference')) {
                $table->string('settlement_reference', 100)->nullable()->after('settled_by_id');
            }
            if (!Schema::hasColumn('orders', 'is_delivery_fee_refunded')) {
                $table->boolean('is_delivery_fee_refunded')->default(0)->after('settlement_reference');
            }

            // Indexes for post-receipt window queries & settlement state filters
            $table->index(['received_at', 'refund_window_expires_at'], 'orders_receipt_window_index');
            $table->index(['vendor_settlement_status'], 'orders_vendor_settlement_status_index');
        });

        // -------------------------------------------------------------
        // Legacy Order Backfill (Commit 7 Safeguard 1)
        // -------------------------------------------------------------

        // Step 1: Backfill received_at for pickup orders using authoritative handed_over_at
        DB::statement("
            UPDATE orders
            SET received_at = handed_over_at,
                refund_window_expires_at = DATE_ADD(handed_over_at, INTERVAL 24 HOUR)
            WHERE order_type = 'pickup'
              AND order_status IN ('delivered', 'returned')
              AND handed_over_at IS NOT NULL
              AND received_at IS NULL
        ");

        // Step 2: Backfill received_at for delivery orders using best-available proxy (updated_at)
        // [AI] IMPORTANT: updated_at is used as a best-available proxy for delivery receipt time.
        // This is imprecise. If a concurrent field update occurred after delivery status was set,
        // updated_at may reflect that later event, not the actual delivery. Accept this imprecision
        // for legacy records; all new Commit 7 orders will have authoritative received_at timestamps.
        DB::statement("
            UPDATE orders
            SET received_at = updated_at,
                refund_window_expires_at = DATE_ADD(updated_at, INTERVAL 24 HOUR)
            WHERE order_type = 'delivery'
              AND order_status IN ('delivered', 'returned')
              AND received_at IS NULL
        ");

        // Step 3: Classify by financial state (settled / refunded)
        DB::statement("
            UPDATE orders SET vendor_settlement_status = 'settled'
            WHERE seller_is = 'seller'
              AND order_status IN ('delivered', 'returned')
              AND id IN (SELECT order_id FROM order_transactions WHERE status = 'disburse')
        ");

        DB::statement("
            UPDATE orders SET vendor_settlement_status = 'refunded'
            WHERE seller_is = 'seller'
              AND id IN (SELECT order_id FROM order_transactions WHERE status = 'refunded')
        ");

        // Step 4: Active third-party orders with backfilled received_at -> held (eligible for V1 lifecycle)
        DB::statement("
            UPDATE orders SET vendor_settlement_status = 'held'
            WHERE seller_is = 'seller'
              AND received_at IS NOT NULL
              AND vendor_settlement_status IS NULL
        ");

        // Step 5: Unresolvable legacy orders -> legacy_hold sentinel
        // [AI] Scope ONLY to historically delivered/returned orders.
        // Do NOT flag unfulfilled or still-active orders (pending, processing, out_for_delivery)
        // as legacy settlement problems. Those remain NULL and follow their normal order lifecycle.
        DB::statement("
            UPDATE orders SET vendor_settlement_status = 'legacy_hold'
            WHERE seller_is = 'seller'
              AND order_status IN ('delivered', 'returned')
              AND received_at IS NULL
              AND vendor_settlement_status IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_receipt_window_index');
            $table->dropIndex('orders_vendor_settlement_status_index');

            $columns = [
                'received_at',
                'refund_window_expires_at',
                'vendor_settlement_status',
                'rider_picked_up_at',
                'rider_picked_up_by',
                'settled_at',
                'settled_by_id',
                'settlement_reference',
                'is_delivery_fee_refunded',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
