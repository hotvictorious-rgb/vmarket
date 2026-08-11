<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            if (!Schema::hasColumn('sellers', 'bank_updated_at')) {
                $table->timestamp('bank_updated_at')->nullable()->after('holder_name');
            }
            if (!Schema::hasColumn('sellers', 'nin')) {
                $table->string('nin', 20)->nullable()->after('bank_updated_at');
            }
            if (!Schema::hasColumn('sellers', 'nin_document')) {
                $table->string('nin_document', 191)->nullable()->after('nin');
            }
            if (!Schema::hasColumn('sellers', 'cac_number')) {
                $table->string('cac_number', 50)->nullable()->after('nin_document');
            }
            if (!Schema::hasColumn('sellers', 'cac_document')) {
                $table->string('cac_document', 191)->nullable()->after('cac_number');
            }
            if (!Schema::hasColumn('sellers', 'kyc_status')) {
                $table->string('kyc_status', 20)->default('pending')->after('cac_document');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->dropColumn([
                'bank_updated_at',
                'nin',
                'nin_document',
                'cac_number',
                'cac_document',
                'kyc_status',
            ]);
        });
    }
};
