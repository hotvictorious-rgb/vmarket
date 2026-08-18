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
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'markup_percentage')) {
                $table->decimal('markup_percentage', 8, 2)->default(10.00)->after('priority');
            }
            if (!Schema::hasColumn('categories', 'markup_type')) {
                $table->string('markup_type', 30)->default('percentage')->after('markup_percentage');
            }
        });

        // Seed default business settings for the dynamic markup & pricing model
        $defaultSettings = [
            ['type' => 'default_platform_markup_percentage', 'value' => '10.00'],
            ['type' => 'pricing_model', 'value' => 'cost_plus_markup'],
            ['type' => 'price_rounding_strategy', 'value' => 'none'],
            ['type' => 'new_product_approval', 'value' => '1'],
        ];

        foreach ($defaultSettings as $setting) {
            if (!DB::table('business_settings')->where('type', $setting['type'])->exists()) {
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
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'markup_percentage')) {
                $table->dropColumn('markup_percentage');
            }
            if (Schema::hasColumn('categories', 'markup_type')) {
                $table->dropColumn('markup_type');
            }
        });
    }
};
