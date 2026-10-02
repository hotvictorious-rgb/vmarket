<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * [AI] VM-VEND-005: vendor entity fields for the CAC-aware onboarding model.
     * Adds business taxonomy + verification audit columns the Seller model,
     * NigerianKycService, and registration already reference but which do not
     * exist (KYC submit currently fatals on missing columns).
     */
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            if (!Schema::hasColumn('sellers', 'business_type')) {
                $table->string('business_type', 20)->default('individual')->after('l_name');
            }
            if (!Schema::hasColumn('sellers', 'legal_name')) {
                $table->string('legal_name', 255)->nullable()->after('business_type');
            }
            if (!Schema::hasColumn('sellers', 'country_code')) {
                $table->string('country_code', 10)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('sellers', 'nin')) {
                $table->string('nin', 30)->nullable()->after('country_code');
            }
            if (!Schema::hasColumn('sellers', 'nin_document')) {
                $table->string('nin_document', 255)->nullable()->after('nin');
            }
            if (!Schema::hasColumn('sellers', 'cac_number')) {
                $table->string('cac_number', 60)->nullable()->after('nin_document');
            }
            if (!Schema::hasColumn('sellers', 'cac_document')) {
                $table->string('cac_document', 255)->nullable()->after('cac_number');
            }
            if (!Schema::hasColumn('sellers', 'cac_status')) {
                $table->string('cac_status', 20)->default('pending')->after('cac_document');
            }
            if (!Schema::hasColumn('sellers', 'kyc_status')) {
                $table->string('kyc_status', 20)->default('pending')->after('cac_status');
            }
            if (!Schema::hasColumn('sellers', 'verification_method')) {
                $table->string('verification_method', 40)->nullable()->after('kyc_status');
            }
            if (!Schema::hasColumn('sellers', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('verification_method');
            }
            if (!Schema::hasColumn('sellers', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }
            if (!Schema::hasColumn('sellers', 'verification_notes')) {
                $table->text('verification_notes')->nullable()->after('verified_at');
            }
            if (!Schema::hasColumn('sellers', 'payout_status')) {
                $table->string('payout_status', 20)->default('pending')->after('verification_notes');
            }
            if (!Schema::hasColumn('sellers', 'marketplace_applied_at')) {
                $table->timestamp('marketplace_applied_at')->nullable()->after('payout_status');
            }
            if (!Schema::hasColumn('sellers', 'marketplace_approved_at')) {
                $table->timestamp('marketplace_approved_at')->nullable()->after('marketplace_applied_at');
            }
        });

        // Backfill: every existing seller is an individual with pending verification.
        DB::table('sellers')->whereNull('business_type')->orWhere('business_type', '')->update(['business_type' => 'individual']);
        DB::table('sellers')->whereNull('cac_status')->orWhere('cac_status', '')->update(['cac_status' => 'pending']);
        DB::table('sellers')->whereNull('kyc_status')->orWhere('kyc_status', '')->update(['kyc_status' => 'pending']);
        DB::table('sellers')->whereNull('payout_status')->orWhere('payout_status', '')->update(['payout_status' => 'pending']);
    }

    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->dropColumn([
                'business_type', 'legal_name', 'country_code', 'nin', 'nin_document',
                'cac_number', 'cac_document', 'cac_status', 'kyc_status',
                'verification_method', 'verified_by', 'verified_at', 'verification_notes',
                'payout_status', 'marketplace_applied_at', 'marketplace_approved_at',
            ]);
        });
    }
};
