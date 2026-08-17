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
        if (!Schema::hasTable('delivery_states')) {
            Schema::create('delivery_states', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('delivery_cities')) {
            Schema::create('delivery_cities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('state_id')->index();
                $table->string('name', 100);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('delivery_hubs')) {
            Schema::create('delivery_hubs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('city_id')->index();
                $table->string('name', 150);
                $table->enum('type', ['landmark', 'motor_park'])->default('landmark')->index();
                $table->decimal('base_shipping_cost', 10, 2)->default(0.00);
                $table->string('estimated_delivery_time', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'origin_hub_id')) {
                $table->unsignedBigInteger('origin_hub_id')->nullable()->index()->after('shipping_address_data');
            }
            if (!Schema::hasColumn('orders', 'destination_hub_id')) {
                $table->unsignedBigInteger('destination_hub_id')->nullable()->index()->after('origin_hub_id');
            }
            if (!Schema::hasColumn('orders', 'house_street_note')) {
                $table->text('house_street_note')->nullable()->after('destination_hub_id');
            }
            if (!Schema::hasColumn('orders', 'recipient_name')) {
                $table->string('recipient_name', 191)->nullable()->after('house_street_note');
            }
            if (!Schema::hasColumn('orders', 'recipient_phone')) {
                $table->string('recipient_phone', 50)->nullable()->after('recipient_name');
            }
            if (!Schema::hasColumn('orders', 'driver_transit_code')) {
                $table->string('driver_transit_code', 50)->nullable()->index()->after('pickup_verification_code');
            }
            if (!Schema::hasColumn('orders', 'driver_phone')) {
                $table->string('driver_phone', 50)->nullable()->after('driver_transit_code');
            }
            if (!Schema::hasColumn('orders', 'driver_vehicle_no')) {
                $table->string('driver_vehicle_no', 50)->nullable()->after('driver_phone');
            }
            if (!Schema::hasColumn('orders', 'waybill_slip_no')) {
                $table->string('waybill_slip_no', 100)->nullable()->after('driver_vehicle_no');
            }
            if (!Schema::hasColumn('orders', 'batch_dispatch_id')) {
                $table->string('batch_dispatch_id', 100)->nullable()->index()->after('waybill_slip_no');
            }
        });

        Schema::table('delivery_men', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_men', 'max_active_orders_limit')) {
                $table->integer('max_active_orders_limit')->default(4)->after('is_active');
            }
        });

        Schema::table('shops', function (Blueprint $table) {
            if (!Schema::hasColumn('shops', 'delivery_state_id')) {
                $table->unsignedBigInteger('delivery_state_id')->nullable()->after('address');
            }
            if (!Schema::hasColumn('shops', 'delivery_city_id')) {
                $table->unsignedBigInteger('delivery_city_id')->nullable()->after('delivery_state_id');
            }
            if (!Schema::hasColumn('shops', 'delivery_hub_id')) {
                $table->unsignedBigInteger('delivery_hub_id')->nullable()->after('delivery_city_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_hubs');
        Schema::dropIfExists('delivery_cities');
        Schema::dropIfExists('delivery_states');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'origin_hub_id',
                'destination_hub_id',
                'house_street_note',
                'recipient_name',
                'recipient_phone',
                'driver_transit_code',
                'driver_phone',
                'driver_vehicle_no',
                'waybill_slip_no',
                'batch_dispatch_id',
            ]);
        });

        Schema::table('delivery_men', function (Blueprint $table) {
            $table->dropColumn('max_active_orders_limit');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_state_id',
                'delivery_city_id',
                'delivery_hub_id',
            ]);
        });
    }
};
