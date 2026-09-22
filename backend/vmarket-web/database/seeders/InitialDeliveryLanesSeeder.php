<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\State;
use App\Models\Lga;
use App\Models\DeliveryLane;

/**
 * [AI] Seeds initial directional delivery lanes for VMarket.
 *
 * Part of: VMarket Geography & Fulfillment Architecture
 * Phase: 6 - Initial Delivery Lanes
 */
class InitialDeliveryLanesSeeder extends Seeder
{
    public function run(): void
    {
        $nigeria = Country::where('iso_code', 'NGA')->first();
        if (!$nigeria) {
            $this->command->error('Nigeria country record not found. Run NigeriaGeographySeeder first.');
            return;
        }

        $akwaIbom = State::where('country_id', $nigeria->id)->where('name', 'Akwa Ibom')->first();
        if (!$akwaIbom) {
            $this->command->error('Akwa Ibom state record not found.');
            return;
        }

        // Get key LGAs in Akwa Ibom
        $uyo = Lga::where('state_id', $akwaIbom->id)->where('name', 'Uyo')->first();
        $eket = Lga::where('state_id', $akwaIbom->id)->where('name', 'Eket')->first();
        $ikotEkpene = Lga::where('state_id', $akwaIbom->id)->where('name', 'Ikot Ekpene')->first();
        $oron = Lga::where('state_id', $akwaIbom->id)->where('name', 'Oron')->first();

        if (!$uyo || !$eket || !$ikotEkpene || !$oron) {
            $this->command->error('Required LGAs not found in Akwa Ibom.');
            return;
        }

        $lanes = [
            // Intra-LGA (same LGA delivery)
            [
                'origin' => $uyo,
                'dest' => $uyo,
                'fee' => 500.00,
                'eta' => '2-6 hours',
                'enabled' => true,
            ],
            [
                'origin' => $eket,
                'dest' => $eket,
                'fee' => 500.00,
                'eta' => '2-6 hours',
                'enabled' => true,
            ],
            [
                'origin' => $ikotEkpene,
                'dest' => $ikotEkpene,
                'fee' => 500.00,
                'eta' => '2-6 hours',
                'enabled' => true,
            ],
            [
                'origin' => $oron,
                'dest' => $oron,
                'fee' => 500.00,
                'eta' => '2-6 hours',
                'enabled' => true,
            ],

            // Inter-LGA (cross-LGA delivery pairs - directional)
            [
                'origin' => $uyo,
                'dest' => $eket,
                'fee' => 1500.00,
                'eta' => '24-48 hours',
                'enabled' => true,
            ],
            [
                'origin' => $eket,
                'dest' => $uyo,
                'fee' => 1500.00,
                'eta' => '24-48 hours',
                'enabled' => true,
            ],
            [
                'origin' => $uyo,
                'dest' => $ikotEkpene,
                'fee' => 1200.00,
                'eta' => '24-48 hours',
                'enabled' => true,
            ],
            [
                'origin' => $ikotEkpene,
                'dest' => $uyo,
                'fee' => 1200.00,
                'eta' => '24-48 hours',
                'enabled' => true,
            ],
            [
                'origin' => $uyo,
                'dest' => $oron,
                'fee' => 1500.00,
                'eta' => '24-48 hours',
                'enabled' => true,
            ],
            [
                'origin' => $oron,
                'dest' => $uyo,
                'fee' => 1500.00,
                'eta' => '24-48 hours',
                'enabled' => true,
            ],
        ];

        foreach ($lanes as $laneData) {
            DeliveryLane::updateOrCreate(
                [
                    'origin_country_id' => $nigeria->id,
                    'origin_state_id' => $akwaIbom->id,
                    'origin_lga_id' => $laneData['origin']->id,
                    'destination_country_id' => $nigeria->id,
                    'destination_state_id' => $akwaIbom->id,
                    'destination_lga_id' => $laneData['dest']->id,
                ],
                [
                    'is_enabled' => $laneData['enabled'],
                    'delivery_fee' => $laneData['fee'],
                    'estimated_delivery_time' => $laneData['eta'],
                ]
            );
        }

        $this->command->info('Seeded ' . count($lanes) . ' initial delivery lanes.');
    }
}
