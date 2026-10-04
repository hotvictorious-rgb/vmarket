<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('order_details', function (Blueprint $t) { $t->text('refund_allocation')->nullable(); }); }
    public function down(): void { Schema::table('order_details', fn(Blueprint $t) => $t->dropColumn('refund_allocation')); }
};
