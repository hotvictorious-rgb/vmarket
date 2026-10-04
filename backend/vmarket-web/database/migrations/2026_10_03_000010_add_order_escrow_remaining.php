<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // [AI] Existing rows remain unknown until verified historical reconciliation; never infer backing.
        Schema::table('order_transactions', function (Blueprint $table) {
            $table->decimal('escrow_remaining', 20, 2)->nullable();
            $table->decimal('recognized_merchandise_remaining', 20, 2)->nullable();
            $table->decimal('recognized_commission_remaining', 20, 2)->nullable();
            $table->decimal('recognized_tax_remaining', 20, 2)->nullable();
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->text('settlement_notes')->nullable();
            $table->string('settlement_method', 30)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_transactions', fn (Blueprint $table) => $table->dropColumn(['escrow_remaining', 'recognized_merchandise_remaining', 'recognized_commission_remaining', 'recognized_tax_remaining']));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['settlement_notes', 'settlement_method']));
    }
};
