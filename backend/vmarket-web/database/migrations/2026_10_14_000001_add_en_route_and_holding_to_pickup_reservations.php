<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [AI] Adds en_route transit handshake and physical counter holding columns
     * to pickup_reservations table for the Nigerian-realistic in-shop inspection protocol.
     */
    public function up(): void
    {
        // 1. Expand enum on MySQL if running on MySQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pickup_reservations MODIFY COLUMN status ENUM(
                'pending_inspection',
                'en_route',
                'en_route_timeout',
                'held_confirmed',
                'inspected_accepted',
                'inspected_rejected',
                'order_placed',
                'canceled',
                'expired'
            ) DEFAULT 'pending_inspection'");
        }

        // 2. Add transit handshake and counter hold timestamps
        Schema::table('pickup_reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('pickup_reservations', 'en_route_at')) {
                $table->timestamp('en_route_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('pickup_reservations', 'en_route_sla_expires_at')) {
                $table->timestamp('en_route_sla_expires_at')->nullable()->after('en_route_at');
            }
            if (!Schema::hasColumn('pickup_reservations', 'en_route_resend_count')) {
                $table->unsignedInteger('en_route_resend_count')->default(0)->after('en_route_sla_expires_at');
            }
            if (!Schema::hasColumn('pickup_reservations', 'last_en_route_ping_at')) {
                $table->timestamp('last_en_route_ping_at')->nullable()->after('en_route_resend_count');
            }
            if (!Schema::hasColumn('pickup_reservations', 'held_at')) {
                $table->timestamp('held_at')->nullable()->after('last_en_route_ping_at');
            }
            if (!Schema::hasColumn('pickup_reservations', 'held_until')) {
                $table->timestamp('held_until')->nullable()->after('held_at');
            }
            if (!Schema::hasColumn('pickup_reservations', 'rejection_reason')) {
                $table->string('rejection_reason', 255)->nullable()->after('held_until');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pickup_reservations', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach ([
                'en_route_at',
                'en_route_sla_expires_at',
                'en_route_resend_count',
                'last_en_route_ping_at',
                'held_at',
                'held_until',
                'rejection_reason',
            ] as $col) {
                if (Schema::hasColumn('pickup_reservations', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pickup_reservations MODIFY COLUMN status ENUM(
                'pending_inspection',
                'inspected_accepted',
                'inspected_rejected',
                'order_placed',
                'canceled',
                'expired'
            ) DEFAULT 'pending_inspection'");
        }
    }
};
