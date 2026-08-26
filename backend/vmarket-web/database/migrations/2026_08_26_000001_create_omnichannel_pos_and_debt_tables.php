<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Customer Debt & Credit Ledger
        if (!Schema::hasTable('pos_customer_ledgers')) {
            Schema::create('pos_customer_ledgers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('seller_id')->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('customer_name');
                $table->string('customer_phone')->nullable()->index();
                $table->string('customer_email')->nullable();
                $table->decimal('total_credit_due', 14, 2)->default(0.00);
                $table->decimal('credit_limit', 14, 2)->default(0.00);
                $table->date('due_date')->nullable();
                $table->string('aging_bucket')->default('current')->index(); // current, due, critical
                $table->boolean('is_credit_blocked')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 2. Debt Transactions & Installment Payments
        if (!Schema::hasTable('pos_debt_transactions')) {
            Schema::create('pos_debt_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ledger_id')->index();
                $table->unsignedBigInteger('seller_id')->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('order_id')->nullable()->index();
                $table->string('transaction_type')->default('repayment')->index(); // debt_issued, repayment, adjustment
                $table->decimal('amount', 14, 2);
                $table->string('payment_method')->nullable(); // cash, pos_card, bank_transfer, wallet
                $table->unsignedBigInteger('collected_by_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 3. Cashier Shifts & Blind-Close Audits
        if (!Schema::hasTable('pos_cashier_shifts')) {
            Schema::create('pos_cashier_shifts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('seller_id')->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('cashier_id')->index();
                $table->timestamp('opened_at')->useCurrent();
                $table->timestamp('closed_at')->nullable();
                $table->decimal('starting_float', 14, 2)->default(0.00);
                $table->decimal('expected_cash', 14, 2)->default(0.00);
                $table->decimal('counted_cash', 14, 2)->default(0.00);
                $table->decimal('variance', 14, 2)->default(0.00);
                $table->string('status')->default('open')->index(); // open, closed
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 4. Inter-Branch Waybills & Logistics
        if (!Schema::hasTable('pos_transfers')) {
            Schema::create('pos_transfers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('seller_id')->index();
                $table->string('waybill_number')->unique();
                $table->unsignedBigInteger('origin_branch_id')->index();
                $table->unsignedBigInteger('destination_branch_id')->index();
                $table->unsignedBigInteger('dispatched_by_id')->nullable();
                $table->unsignedBigInteger('received_by_id')->nullable();
                $table->string('driver_name')->nullable();
                $table->string('driver_phone')->nullable();
                $table->string('vehicle_number')->nullable();
                $table->integer('total_items_dispatched')->default(0);
                $table->integer('total_items_received')->default(0);
                $table->integer('variance_count')->default(0);
                $table->string('status')->default('dispatched')->index(); // dispatched, in_transit, received, variance_flagged
                $table->timestamp('dispatched_at')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 5. Transfer Line Items
        if (!Schema::hasTable('pos_transfer_items')) {
            Schema::create('pos_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('transfer_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->integer('dispatched_quantity');
                $table->integer('received_quantity')->default(0);
                $table->integer('variance_quantity')->default(0);
                $table->decimal('unit_cost', 14, 2)->default(0.00);
                $table->timestamps();
            });
        }

        // 6. POS SaaS Subscriptions & Multi-Branch Plans
        if (!Schema::hasTable('pos_subscriptions')) {
            Schema::create('pos_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('seller_id')->index();
                $table->string('plan_type')->default('starter_free')->index(); // starter_free, multi_branch_pro
                $table->string('billing_cycle')->default('monthly'); // monthly, yearly
                $table->decimal('price_paid', 14, 2)->default(0.00);
                $table->string('payment_method')->default('paystack'); // paystack, offline_payment, wallet, admin_grant
                $table->string('status')->default('active')->index(); // active, expired, pending_verification
                $table->unsignedBigInteger('offline_payment_id')->nullable();
                $table->string('paystack_reference')->nullable()->index();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        // 7. Extend Sellers Table for Marketplace Approval Workflow
        Schema::table('sellers', function (Blueprint $table) {
            if (!Schema::hasColumn('sellers', 'marketplace_status')) {
                $table->string('marketplace_status')->default('pos_only')->after('status')->index(); // pos_only, pending_approval, approved
            }
            if (!Schema::hasColumn('sellers', 'marketplace_applied_at')) {
                $table->timestamp('marketplace_applied_at')->nullable()->after('marketplace_status');
            }
            if (!Schema::hasColumn('sellers', 'marketplace_approved_at')) {
                $table->timestamp('marketplace_approved_at')->nullable()->after('marketplace_applied_at');
            }
        });

        // 8. Extend Shops Table for Multi-Branch Architecture
        Schema::table('shops', function (Blueprint $table) {
            if (!Schema::hasColumn('shops', 'is_primary_branch')) {
                $table->boolean('is_primary_branch')->default(true)->after('seller_id');
            }
            if (!Schema::hasColumn('shops', 'branch_code')) {
                $table->string('branch_code')->nullable()->after('is_primary_branch');
            }
        });

        // 9. Seed Global POS Default Business Settings
        $defaultSettings = [
            'pos_free_branch_limit' => 1,
            'pos_multi_branch_monthly_price' => 15000,
            'pos_multi_branch_annual_price' => 150000,
            'pos_trial_days' => 14,
            'pos_receipt_footer_text' => 'Powered by Victorious MARKET - Your Trusted Online Market',
            'pos_reorder_qr_status' => 1,
        ];

        foreach ($defaultSettings as $type => $value) {
            $exists = DB::table('business_settings')->where('type', $type)->exists();
            if (!$exists) {
                DB::table('business_settings')->insert([
                    'type' => $type,
                    'value' => json_encode($value),
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
        Schema::dropIfExists('pos_transfer_items');
        Schema::dropIfExists('pos_transfers');
        Schema::dropIfExists('pos_cashier_shifts');
        Schema::dropIfExists('pos_debt_transactions');
        Schema::dropIfExists('pos_customer_ledgers');
        Schema::dropIfExists('pos_subscriptions');

        Schema::table('sellers', function (Blueprint $table) {
            if (Schema::hasColumn('sellers', 'marketplace_status')) {
                $table->dropColumn(['marketplace_status', 'marketplace_applied_at', 'marketplace_approved_at']);
            }
        });

        Schema::table('shops', function (Blueprint $table) {
            if (Schema::hasColumn('shops', 'is_primary_branch')) {
                $table->dropColumn(['is_primary_branch', 'branch_code']);
            }
        });
    }
};
