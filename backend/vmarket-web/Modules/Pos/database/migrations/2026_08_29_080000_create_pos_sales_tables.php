<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [AI] Creates all POS-specific transactional tables in the unified Vmarket database.
 *
 * These tables are migrated from the standalone Hysam POS SQLite database.
 * They use the `pos_` prefix to clearly distinguish them from marketplace tables.
 *
 * KEY DESIGN DECISIONS:
 * - `pos_sales.seller_id`       → FK to `sellers.id` (replaces Hysam `companies.marketplace_vendor_id`)
 * - `pos_sales.branch_id`       → FK to `shops.id` (replaces Hysam `warehouses.id`)
 * - `pos_sales.cashier_id`      → FK to `vendor_employees.id` (replaces Hysam `users.id`)
 * - Products are unified: `pos_sale_items.product_id` → FK to `products.id`
 * - `pos_product_legacy_map`    → bridges Hysam UUID product IDs to Vmarket int product IDs
 *
 * Clients affected: Verified + Unverified Merchant POS terminals.
 * Clients NOT affected: Customer Web Storefront, Flutter Apps, Admin Panel (read-only dashboards).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────────────────
        // 1. POS Sales (In-Store Transactions)
        // ─────────────────────────────────────────────────────────
        if (!Schema::hasTable('pos_sales')) {
            Schema::create('pos_sales', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('seller_id')->index();           // [AI] Which vendor's store
                $table->unsignedBigInteger('branch_id')->nullable()->index(); // [AI] Which branch/shop
                $table->unsignedBigInteger('cashier_id')->nullable()->index(); // [AI] Which employee rang this sale
                $table->string('cashier_name')->nullable();                 // [AI] Snapshot for history
                $table->string('customer_name')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable()->index(); // [AI] Link to unified customers table
                $table->string('customer_phone')->nullable();
                $table->decimal('total_amount', 14, 2)->default(0.00);
                $table->decimal('paid_amount', 14, 2)->default(0.00);
                $table->decimal('cash_amount', 14, 2)->default(0.00);
                $table->decimal('pos_card_amount', 14, 2)->default(0.00);
                $table->decimal('transfer_amount', 14, 2)->default(0.00);
                $table->decimal('debt_amount', 14, 2)->default(0.00);       // [AI] Amount added to debt ledger
                $table->string('status')->default('completed')->index();    // completed, returned, voided
                $table->string('delivery_status')->nullable();              // pending, delivered, returned
                $table->text('note')->nullable();
                $table->string('receipt_number')->unique()->nullable();
                $table->boolean('is_wholesale')->default(false);
                $table->timestamps();

                $table->foreign('seller_id')->references('id')->on('sellers')->onDelete('cascade');
            });
        }

        // ─────────────────────────────────────────────────────────
        // 2. POS Sale Line Items
        // ─────────────────────────────────────────────────────────
        if (!Schema::hasTable('pos_sale_items')) {
            Schema::create('pos_sale_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pos_sale_id')->index();
                $table->unsignedBigInteger('product_id')->index();           // [AI] FK to unified products table
                $table->string('product_name');                              // [AI] Snapshot at time of sale
                $table->string('product_code')->nullable();
                $table->integer('quantity');
                $table->decimal('unit_price', 14, 2);
                $table->decimal('total_price', 14, 2);
                $table->decimal('purchase_price', 14, 2)->default(0.00);    // [AI] For margin reporting
                $table->timestamps();

                $table->foreign('pos_sale_id')->references('id')->on('pos_sales')->onDelete('cascade');
            });
        }

        // ─────────────────────────────────────────────────────────
        // 3. POS In-Store Payments (per sale, multi-method)
        // ─────────────────────────────────────────────────────────
        if (!Schema::hasTable('pos_payments')) {
            Schema::create('pos_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pos_sale_id')->index();
                $table->unsignedBigInteger('seller_id')->index();
                $table->decimal('amount', 14, 2);
                $table->string('method');                                    // cash, pos_card, bank_transfer, debt
                $table->string('reference_no')->nullable();
                $table->string('recorded_by')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->foreign('pos_sale_id')->references('id')->on('pos_sales')->onDelete('cascade');
            });
        }

        // ─────────────────────────────────────────────────────────
        // 4. POS Sales Returns / Refunds
        // ─────────────────────────────────────────────────────────
        if (!Schema::hasTable('pos_sales_returns')) {
            Schema::create('pos_sales_returns', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pos_sale_id')->index();
                $table->unsignedBigInteger('seller_id')->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->string('product_name');
                $table->string('product_code')->nullable();
                $table->integer('quantity');
                $table->decimal('refund_amount', 14, 2);
                $table->text('reason')->nullable();
                $table->boolean('stock_restocked')->default(false);          // [AI] Whether stock was added back
                $table->string('processed_by')->nullable();
                $table->timestamps();
            });
        }

        // ─────────────────────────────────────────────────────────
        // 5. POS Inventory Logs (Stock Movement Audit Trail)
        // ─────────────────────────────────────────────────────────
        if (!Schema::hasTable('pos_inventory_logs')) {
            Schema::create('pos_inventory_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('seller_id')->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->string('product_name');
                $table->string('product_code')->nullable();
                $table->string('type')->index();                             // STOCK_IN, SALE, RETURN, ADJUSTMENT, TRANSFER_OUT, TRANSFER_IN
                $table->integer('quantity_change');                          // +ve = added, -ve = removed
                $table->integer('stock_before')->default(0);
                $table->integer('stock_after')->default(0);
                $table->string('reference_id')->nullable();                  // pos_sale_id or transfer_id
                $table->string('reference_type')->nullable();                // pos_sale, pos_transfer, adjustment
                $table->string('recorded_by')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // ─────────────────────────────────────────────────────────
        // 6. POS Activity / Audit Log
        // ─────────────────────────────────────────────────────────
        if (!Schema::hasTable('pos_activities')) {
            Schema::create('pos_activities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('seller_id')->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('actor_id');                                  // vendor_employee id or seller id
                $table->string('actor_name');
                $table->string('type')->index();                             // LOGIN, SALE, STOCK_IN, TRANSFER, RETURN, REFUND, SHIFT_OPEN, SHIFT_CLOSE
                $table->text('description');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // ─────────────────────────────────────────────────────────
        // 7. Product Legacy ID Map (Hysam UUID → Vmarket int ID)
        // Bridges historical Hysam product UUID references.
        // ─────────────────────────────────────────────────────────
        if (!Schema::hasTable('pos_product_legacy_map')) {
            Schema::create('pos_product_legacy_map', function (Blueprint $table) {
                $table->id();
                $table->string('hysam_uuid', 64)->unique()->index();         // Old Hysam UUID product PK
                $table->unsignedBigInteger('product_id')->index();           // Unified Vmarket product bigint PK
                $table->unsignedBigInteger('seller_id')->index();
                $table->timestamps();
            });
        }

        // ─────────────────────────────────────────────────────────
        // 8. POS Stock Adjustments
        // ─────────────────────────────────────────────────────────
        if (!Schema::hasTable('pos_stock_adjustments')) {
            Schema::create('pos_stock_adjustments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('seller_id')->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->integer('quantity_change');                          // +ve or -ve
                $table->string('reason');                                    // DAMAGED, EXPIRED, FOUND, SHRINKAGE, OTHER
                $table->string('adjusted_by')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // ─────────────────────────────────────────────────────────
        // 9. Add POS-specific columns to vendor_employees
        // ─────────────────────────────────────────────────────────
        Schema::table('vendor_employees', function (Blueprint $table) {
            if (!Schema::hasColumn('vendor_employees', 'assigned_branch_id')) {
                $table->unsignedBigInteger('assigned_branch_id')->nullable()->after('vendor_role_id')->index();
                // [AI] Which shop/branch this cashier/staff is assigned to
            }
            if (!Schema::hasColumn('vendor_employees', 'pos_pin')) {
                $table->string('pos_pin', 6)->nullable()->after('assigned_branch_id');
                // [AI] Optional 6-digit quick-login PIN for POS terminal
            }
            if (!Schema::hasColumn('vendor_employees', 'can_access_pos')) {
                $table->boolean('can_access_pos')->default(true)->after('pos_pin');
                // [AI] Toggle POS access independently of other permissions
            }
        });

        // ─────────────────────────────────────────────────────────
        // 10. Add POS-specific columns to products
        // ─────────────────────────────────────────────────────────
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'pos_barcode')) {
                $table->string('pos_barcode')->nullable()->after('code')->index();
                // [AI] Secondary barcode field for POS scanner (distinct from marketplace `code`)
            }
            if (!Schema::hasColumn('products', 'pos_category')) {
                $table->string('pos_category')->nullable()->after('pos_barcode');
                // [AI] Human-readable POS category string (e.g. "Beverages") — mirrors Hysam's flat category
            }
            if (!Schema::hasColumn('products', 'pos_reorder_level')) {
                $table->integer('pos_reorder_level')->default(5)->after('pos_category');
                // [AI] Minimum stock alert threshold for POS (equivalent to Hysam minStockLevel)
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_stock_adjustments');
        Schema::dropIfExists('pos_product_legacy_map');
        Schema::dropIfExists('pos_activities');
        Schema::dropIfExists('pos_inventory_logs');
        Schema::dropIfExists('pos_sales_returns');
        Schema::dropIfExists('pos_payments');
        Schema::dropIfExists('pos_sale_items');
        Schema::dropIfExists('pos_sales');

        Schema::table('vendor_employees', function (Blueprint $table) {
            foreach (['assigned_branch_id', 'pos_pin', 'can_access_pos'] as $col) {
                if (Schema::hasColumn('vendor_employees', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('products', function (Blueprint $table) {
            foreach (['pos_barcode', 'pos_category', 'pos_reorder_level'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
