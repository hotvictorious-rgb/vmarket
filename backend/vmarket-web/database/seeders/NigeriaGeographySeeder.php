<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\State;
use App\Models\Lga;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * [AI] Seeds Nigeria's canonical geography (36 states + FCT, 774 LGAs).
 *
 * Part of: VMarket Geography & Fulfillment Architecture
 * Phase: 3 - Nigeria Geography Seed
 *
 * Source: INEC Nigeria official directory
 *
 * IMPORTANT:
 * - DO NOT hard-code geography in Flutter
 * - Backend is authoritative
 * - Uses firstOrCreate to be idempotent (safe to run multiple times)
 */
class NigeriaGeographySeeder extends Seeder
{
    public function run(): void
    {
        Log::info('[AI] Starting Nigeria geography seed...');

        // Create Nigeria country
        $nigeria = Country::firstOrCreate([
            'iso_code' => 'NGA',
        ], [
            'name' => 'Nigeria',
            'is_active' => true,
        ]);

        Log::info("[AI] Country created/found: {$nigeria->name} (ID: {$nigeria->id})");

        // Load geography data from JSON
        $jsonPath = database_path('seeders/data/nigeria-states-lgas.json');

        if (!File::exists($jsonPath)) {
            Log::error("[AI] Geography data file not found: {$jsonPath}");
            $this->command->error('Geography data file not found!');
            return;
        }

        $data = json_decode(File::get($jsonPath), true);

        if (!$data || !isset($data['states'])) {
            Log::error('[AI] Invalid geography data format');
            $this->command->error('Invalid geography data format!');
            return;
        }

        $stateCount = 0;
        $lgaCount = 0;

        foreach ($data['states'] as $stateData) {
            // Create state
            $state = State::firstOrCreate([
                'country_id' => $nigeria->id,
                'name' => $stateData['name'],
            ], [
                'code' => $stateData['code'] ?? null,
                'is_active' => true,
            ]);

            $stateCount++;

            // Create LGAs for this state
            foreach ($stateData['lgas'] as $lgaName) {
                Lga::firstOrCreate([
                    'state_id' => $state->id,
                    'name' => $lgaName,
                ], [
                    'is_active' => true,
                ]);

                $lgaCount++;
            }

            $this->command->info("✓ {$state->name}: " . count($stateData['lgas']) . " LGAs");
        }

        Log::info("[AI] Geography seed complete: {$stateCount} states, {$lgaCount} LGAs");
        $this->command->info("\n✅ Seeded {$stateCount} states and {$lgaCount} LGAs for Nigeria");
    }
}
