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
        if (!Schema::hasTable('pos_branch_stocks')) {
            Schema::create('pos_branch_stocks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('seller_id')->index();
                $table->unsignedBigInteger('branch_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->integer('stock_quantity')->default(0);
                $table->integer('reorder_level')->default(5);
                $table->timestamps();

                $table->unique(['branch_id', 'product_id'], 'branch_product_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_branch_stocks');
    }
};
