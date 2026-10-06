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
        // 1. Create logistics_companies table
        if (!Schema::hasTable('logistics_companies')) {
            Schema::create('logistics_companies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('company_email')->unique();
                $table->string('company_phone');
                $table->string('contact_person_name')->nullable();
                $table->string('contact_person_phone')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->string('cac_number')->nullable();
                $table->text('address')->nullable();
                $table->unsignedBigInteger('state_id')->nullable()->index();
                $table->unsignedBigInteger('lga_id')->nullable()->index();
                $table->json('operating_lgas')->nullable();
                $table->string('logo')->nullable();
                $table->string('tin_document')->nullable();
                $table->string('cac_document')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('account_number')->nullable();
                $table->string('account_name')->nullable();
                $table->string('status')->default('active')->index(); // pending, active, suspended, rejected
                $table->boolean('is_active')->default(1)->index();
                $table->timestamps();
            });
        }

        // 2. Create logistics_company_wallets table
        if (!Schema::hasTable('logistics_company_wallets')) {
            Schema::create('logistics_company_wallets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('logistics_company_id')->unique()->index();
                $table->decimal('total_earned', 14, 2)->default(0.00);
                $table->decimal('withdrawn', 14, 2)->default(0.00);
                $table->decimal('pending_withdraw', 14, 2)->default(0.00);
                $table->decimal('current_balance', 14, 2)->default(0.00);
                $table->timestamps();

                $table->foreign('logistics_company_id')->references('id')->on('logistics_companies')->onDelete('cascade');
            });
        }

        // 3. Create logistics_company_transactions table
        if (!Schema::hasTable('logistics_company_transactions')) {
            Schema::create('logistics_company_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('logistics_company_id')->index();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->unsignedBigInteger('delivery_man_id')->nullable()->index();
                $table->decimal('gross_delivery_fee', 14, 2)->default(0.00);
                $table->decimal('admin_commission_rate', 5, 2)->default(0.00);
                $table->decimal('admin_commission_amount', 14, 2)->default(0.00);
                $table->decimal('net_partner_amount', 14, 2)->default(0.00);
                $table->string('transaction_type', 50)->default('delivery_credit')->index(); // delivery_credit, withdrawal, refund_debit, adjustment
                $table->decimal('balance_before', 14, 2)->default(0.00);
                $table->decimal('balance_after', 14, 2)->default(0.00);
                $table->text('transaction_note')->nullable();
                $table->timestamps();

                $table->foreign('logistics_company_id')->references('id')->on('logistics_companies')->onDelete('cascade');
            });
        }

        // 4. Create logistics_company_withdraw_requests table
        if (!Schema::hasTable('logistics_company_withdraw_requests')) {
            Schema::create('logistics_company_withdraw_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('logistics_company_id')->index();
                $table->decimal('amount', 14, 2);
                $table->string('bank_name')->nullable();
                $table->string('account_number')->nullable();
                $table->string('account_name')->nullable();
                $table->string('status')->default('pending')->index(); // pending, approved, denied
                $table->text('transaction_note')->nullable();
                $table->text('admin_note')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->foreign('logistics_company_id')->references('id')->on('logistics_companies')->onDelete('cascade');
            });
        }

        // 5. Alter delivery_men table
        if (Schema::hasTable('delivery_men')) {
            Schema::table('delivery_men', function (Blueprint $table) {
                if (!Schema::hasColumn('delivery_men', 'logistics_company_id')) {
                    $table->unsignedBigInteger('logistics_company_id')->nullable()->after('seller_id')->index();
                }
                if (!Schema::hasColumn('delivery_men', 'vehicle_type')) {
                    $table->string('vehicle_type')->default('motorbike')->after('identity_type')->index(); // motorbike, tricycle, van, truck
                }
            });
        }

        // 6. Seed business_settings defaults
        $settings = [
            ['type' => 'delivery_commission_percentage', 'value' => '15'],
            ['type' => 'bulky_cargo_surcharge', 'value' => '2500'],
            ['type' => 'enable_logistics_company_module', 'value' => '1'],
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
        if (Schema::hasTable('delivery_men')) {
            Schema::table('delivery_men', function (Blueprint $table) {
                if (Schema::hasColumn('delivery_men', 'logistics_company_id')) {
                    $table->dropColumn('logistics_company_id');
                }
                if (Schema::hasColumn('delivery_men', 'vehicle_type')) {
                    $table->dropColumn('vehicle_type');
                }
            });
        }

        Schema::dropIfExists('logistics_company_withdraw_requests');
        Schema::dropIfExists('logistics_company_transactions');
        Schema::dropIfExists('logistics_company_wallets');
        Schema::dropIfExists('logistics_companies');
    }
};
