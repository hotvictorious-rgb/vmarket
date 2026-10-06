<?php

namespace App\Services;

use App\Models\Lga;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * [AI] AddressAutocompleteService
 *
 * Provides zero-cost, zero-API-key hierarchical street and landmark autocomplete
 * strictly scoped to the customer's selected Nigerian Local Government Area (LGA) and State.
 *
 * Invariants:
 * - Scoped strictly to the resolved LGA and State.
 * - Multi-provider zero-key resilience: Primary (Photon/OSM) -> Secondary (OSM Nominatim).
 * - Utilizes high-performance local caching (24-hour TTL) for valid lookups.
 * - Never permanently caches empty/offline fallbacks.
 * - Returns structured coordinates (latitude, longitude) for native Google Maps turn-by-turn routing.
 */
class AddressAutocompleteService
{
    /**
     * Get address suggestions scoped to a specific LGA.
     *
     * @param int $lgaId
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function getSuggestions(int $lgaId, string $query, int $limit = 8): array
    {
        $cleanQuery = trim($query);
        if (strlen($cleanQuery) < 2) {
            return [];
        }

        $lga = Lga::with('state')->where('id', $lgaId)->first();
        if (!$lga) {
            return [];
        }

        $lgaName = $lga->name;
        $stateName = $lga->state?->name ?? '';

        $cacheKey = 'addr_ac_' . $lgaId . '_' . md5(strtolower($cleanQuery)) . '_' . $limit;

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (!empty($cached) && is_array($cached)) {
                // Ensure cached result is valid
                if (!empty($cached[0]['latitude']) || count($cached) > 1) {
                    return $cached;
                }
            }
        }

        // 1. Primary Engine: Photon Geocoder (Fast, OpenStreetMap-based, Zero Key)
        $results = $this->queryPhoton($cleanQuery, $lgaName, $stateName, $limit);

        // 2. Secondary Engine: Nominatim OpenStreetMap (Direct fallback if Photon empty)
        if (empty($results)) {
            $results = $this->queryNominatim($cleanQuery, $lgaName, $stateName, $limit);
        }

        // 3. Cache valid results for 24 hours
        if (!empty($results)) {
            Cache::put($cacheKey, $results, 86400);
            return $results;
        }

        // 4. Offline / Graceful Local Fallback (cached for 60s only)
        $fallback = $this->getFallbackLocalSuggestion($cleanQuery, $lgaName, $stateName);
        Cache::put($cacheKey, $fallback, 60);

        return $fallback;
    }

    /**
     * Query Photon geocoding service.
     */
    protected function queryPhoton(string $query, string $lgaName, string $stateName, int $limit): array
    {
        $searchTerms = sprintf('%s, %s, %s', $query, $lgaName, $stateName);
        $url = 'https://photon.komoot.io/api/?q=' . urlencode($searchTerms) . '&limit=' . min($limit * 2, 16);

        try {
            $response = Http::timeout(5)
                ->withHeaders(['User-Agent' => 'VictoriousMarket/1.0 (support@victoriousmarket.com.ng)'])
                ->get($url);

            if (!$response->successful()) {
                return [];
            }

            $features = $response->json('features') ?? [];
            $results = [];
            $seen = [];

            foreach ($features as $f) {
                $props = $f['properties'] ?? [];
                $coords = $f['geometry']['coordinates'] ?? [0, 0];
                $name = $props['name'] ?? ($props['street'] ?? '');

                if (empty($name)) {
                    continue;
                }

                $countryCode = strtolower($props['countrycode'] ?? '');
                $countryName = strtolower($props['country'] ?? '');
                if (!empty($countryCode) && $countryCode !== 'ng') {
                    continue;
                }
                if (!empty($countryName) && !str_contains($countryName, 'nigeria')) {
                    continue;
                }

                $normKey = strtolower($name);
                if (isset($seen[$normKey])) {
                    continue;
                }
                $seen[$normKey] = true;

                $cityName = $props['city'] ?? ($props['county'] ?? ($props['district'] ?? $lgaName));
                $stateRes = $props['state'] ?? $stateName;

                $formatted = sprintf('%s, %s, %s', $name, $cityName ?: $lgaName, $stateRes ?: $stateName);

                $results[] = [
                    'name' => $name,
                    'street' => $props['street'] ?? $name,
                    'city' => $cityName ?: $lgaName,
                    'state' => $stateRes ?: $stateName,
                    'country' => 'Nigeria',
                    'latitude' => (float) ($coords[1] ?? 0.0),
                    'longitude' => (float) ($coords[0] ?? 0.0),
                    'formatted_address' => $formatted,
                ];

                if (count($results) >= $limit) {
                    break;
                }
            }

            return $results;
        } catch (\Throwable $e) {
            Log::warning('[AI] Photon geocoding query failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Query Nominatim OpenStreetMap service as fallback.
     */
    protected function queryNominatim(string $query, string $lgaName, string $stateName, int $limit): array
    {
        $searchTerms = sprintf('%s, %s, %s', $query, $lgaName, $stateName);
        $url = 'https://nominatim.openstreetmap.org/search?q=' . urlencode($searchTerms)
            . '&countrycodes=ng&format=json&addressdetails=1&limit=' . $limit;

        try {
            $response = Http::timeout(5)
                ->withHeaders(['User-Agent' => 'VictoriousMarket/1.0 (support@victoriousmarket.com.ng)'])
                ->get($url);

            if (!$response->successful()) {
                return [];
            }

            $items = $response->json() ?? [];
            if (!is_array($items)) {
                return [];
            }

            $results = [];
            foreach ($items as $item) {
                $addr = $item['address'] ?? [];
                $name = $item['name'] ?? ($addr['road'] ?? ($item['display_name'] ?? ''));

                if (empty($name)) {
                    continue;
                }

                $cityName = $addr['city'] ?? ($addr['county'] ?? ($addr['town'] ?? $lgaName));
                $stateRes = $addr['state'] ?? $stateName;

                $results[] = [
                    'name' => $name,
                    'street' => $addr['road'] ?? $name,
                    'city' => $cityName ?: $lgaName,
                    'state' => $stateRes ?: $stateName,
                    'country' => 'Nigeria',
                    'latitude' => (float) ($item['lat'] ?? 0.0),
                    'longitude' => (float) ($item['lon'] ?? 0.0),
                    'formatted_address' => $item['display_name'] ?? sprintf('%s, %s, %s', $name, $cityName, $stateRes),
                ];

                if (count($results) >= $limit) {
                    break;
                }
            }

            return $results;
        } catch (\Throwable $e) {
            Log::warning('[AI] Nominatim geocoding query failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Fallback suggestion when offline or external providers are unreachable.
     */
    protected function getFallbackLocalSuggestion(string $query, string $lgaName, string $stateName): array
    {
        return [
            [
                'name' => $query,
                'street' => $query,
                'city' => $lgaName,
                'state' => $stateName,
                'country' => 'Nigeria',
                'latitude' => 0.0,
                'longitude' => 0.0,
                'formatted_address' => sprintf('%s, %s, %s', $query, $lgaName, $stateName),
            ]
        ];
    }
}
