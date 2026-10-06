<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use App\Utils\Helpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * [AI] MapApiController
 *
 * Provides map services with zero-cost, zero-API-key fallback.
 * When Google Map API key is not configured or disabled, transparently falls back
 * to OpenStreetMap / Nominatim / Photon services without breaking mobile app clients.
 */
class MapApiController extends Controller
{
    /**
     * Autocomplete places search (Google Places API compatible).
     */
    public function placeApiAutocomplete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'search_text' => 'required',
        ]);

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $apiKey = getWebConfig(name: 'map_api_key_server');
        $mapStatus = (int) (getWebConfig(name: 'map_api_status') ?? 0);

        if ($mapStatus === 1 && !empty($apiKey)) {
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Goog-Api-Key' => $apiKey,
                    'X-Goog-FieldMask' => '*'
                ])->post('https://places.googleapis.com/v1/places:autocomplete', [
                    'input' => $request->input('search_text'),
                ]);

                if ($response->successful()) {
                    return response()->json($response->json());
                }
            } catch (\Throwable $e) {
                Log::warning('[AI] Google Places API autocomplete error: ' . $e->getMessage());
            }
        }

        // Zero-Key OpenStreetMap Fallback
        return $this->zeroKeyPlaceAutocomplete($request->input('search_text'));
    }

    /**
     * Distance calculation between two points (ComputeRouteMatrix compatible).
     */
    public function distanceApi(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'origin_lat' => 'required|numeric',
            'origin_lng' => 'required|numeric',
            'destination_lat' => 'required|numeric',
            'destination_lng' => 'required|numeric',
        ]);

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $originLat = (float) $request['origin_lat'];
        $originLng = (float) $request['origin_lng'];
        $destLat = (float) $request['destination_lat'];
        $destLng = (float) $request['destination_lng'];

        $apiKey = getWebConfig(name: 'map_api_key_server');
        $mapStatus = (int) (getWebConfig(name: 'map_api_status') ?? 0);

        if ($mapStatus === 1 && !empty($apiKey)) {
            try {
                $origin = [
                    "waypoint" => [
                        "location" => [
                            "latLng" => [
                                "latitude" => $originLat,
                                "longitude" => $originLng,
                            ]
                        ]
                    ]
                ];

                $destination = [
                    "waypoint" => [
                        "location" => [
                            "latLng" => [
                                "latitude" => $destLat,
                                "longitude" => $destLng,
                            ]
                        ]
                    ]
                ];

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Goog-Api-Key' => $apiKey,
                    'X-Goog-FieldMask' => '*'
                ])->post('https://routes.googleapis.com/distanceMatrix/v2:computeRouteMatrix', [
                    "origins" => $origin,
                    "destinations" => $destination,
                    "travelMode" => "DRIVE",
                    "routingPreference" => "TRAFFIC_AWARE"
                ]);

                if ($response->successful()) {
                    return response()->json($response->json());
                }
            } catch (\Throwable $e) {
                Log::warning('[AI] Google Routes API distance error: ' . $e->getMessage());
            }
        }

        // Zero-Key Haversine Distance Fallback
        $distanceMeters = $this->calculateHaversineDistance($originLat, $originLng, $destLat, $destLng);
        // Estimate driving duration at ~35 km/h (9.7 m/s) average city driving in Nigeria
        $durationSeconds = max(60, round($distanceMeters / 9.72));

        return response()->json([
            [
                "originIndex" => 0,
                "destinationIndex" => 0,
                "status" => new \stdClass(),
                "distanceMeters" => $distanceMeters,
                "duration" => $durationSeconds . "s",
                "condition" => "ROUTE_EXISTS"
            ]
        ]);
    }

    /**
     * Place details lookup.
     */
    public function placeApiDetails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'placeid' => 'required',
        ]);

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $placeId = (string) $request['placeid'];

        // Handle zero-key encoded place ID
        if (str_starts_with($placeId, 'zero_')) {
            $decoded = base64_decode(substr($placeId, 5));
            if ($decoded && str_contains($decoded, '|')) {
                [$coords, $addr] = explode('|', $decoded, 2);
                [$lat, $lng] = explode(',', $coords);

                return response()->json([
                    'location' => [
                        'latitude' => (float) $lat,
                        'longitude' => (float) $lng,
                    ],
                    'formatted_address' => $addr,
                ]);
            }
        }

        $apiKey = getWebConfig(name: 'map_api_key_server');
        $mapStatus = (int) (getWebConfig(name: 'map_api_status') ?? 0);

        if ($mapStatus === 1 && !empty($apiKey)) {
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Goog-Api-Key' => $apiKey,
                    'X-Goog-FieldMask' => '*'
                ])->get('https://places.googleapis.com/v1/places/' . $placeId);

                if ($response->successful()) {
                    return response()->json($response->json());
                }
            } catch (\Throwable $e) {
                Log::warning('[AI] Google Places API details error: ' . $e->getMessage());
            }
        }

        // Default empty location fallback
        return response()->json([
            'location' => [
                'latitude' => 5.0236, // Uyo center fallback
                'longitude' => 7.9237,
            ],
            'formatted_address' => 'Nigeria',
        ]);
    }

    /**
     * Reverse geocoding (lat, lng -> address).
     */
    public function geocode_api(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $lat = (float) $request['lat'];
        $lng = (float) $request['lng'];

        $apiKey = getWebConfig(name: 'map_api_key_server');
        $mapStatus = (int) (getWebConfig(name: 'map_api_status') ?? 0);

        if ($mapStatus === 1 && !empty($apiKey)) {
            try {
                $response = Http::get("https://maps.googleapis.com/maps/api/geocode/json?latlng={$lat},{$lng}&key={$apiKey}");
                if ($response->successful()) {
                    return response()->json($response->json());
                }
            } catch (\Throwable $e) {
                Log::warning('[AI] Google Geocode API error: ' . $e->getMessage());
            }
        }

        // Zero-Key Reverse Geocode via OpenStreetMap Nominatim
        $cacheKey = 'rev_geo_' . round($lat, 5) . '_' . round($lng, 5);
        $result = Cache::remember($cacheKey, 86400, function () use ($lat, $lng) {
            try {
                $nomUrl = "https://nominatim.openstreetmap.org/reverse?lat={$lat}&lon={$lng}&format=json&addressdetails=1";
                $res = Http::timeout(5)
                    ->withHeaders(['User-Agent' => 'VictoriousMarket/1.0 (support@victoriousmarket.com.ng)'])
                    ->get($nomUrl);

                if ($res->successful()) {
                    $data = $res->json();
                    $displayName = $data['display_name'] ?? 'Identified Location';
                    return [
                        'status' => 'OK',
                        'results' => [
                            [
                                'formatted_address' => $displayName,
                                'place_id' => 'osm_' . ($data['place_id'] ?? 'loc'),
                                'geometry' => [
                                    'location' => [
                                        'lat' => $lat,
                                        'lng' => $lng,
                                    ]
                                ]
                            ]
                        ]
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('[AI] Nominatim reverse geocode failed: ' . $e->getMessage());
            }

            return [
                'status' => 'OK',
                'results' => [
                    [
                        'formatted_address' => 'Customer Address (' . round($lat, 4) . ', ' . round($lng, 4) . ')',
                        'place_id' => 'custom_loc',
                        'geometry' => [
                            'location' => [
                                'lat' => $lat,
                                'lng' => $lng,
                            ]
                        ]
                    ]
                ]
            ];
        });

        return response()->json($result);
    }

    /**
     * Zero-key OpenStreetMap/Photon search autocomplete.
     */
    protected function zeroKeyPlaceAutocomplete(string $searchText): JsonResponse
    {
        $clean = trim($searchText);
        if (strlen($clean) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $cacheKey = 'zero_ac_' . md5(strtolower($clean));
        $suggestions = Cache::remember($cacheKey, 3600, function () use ($clean) {
            $results = [];

            // 1. Try Nominatim Search
            try {
                $nomUrl = "https://nominatim.openstreetmap.org/search?q=" . urlencode($clean) . "&countrycodes=ng&format=json&addressdetails=1&limit=6";
                $res = Http::timeout(5)
                    ->withHeaders(['User-Agent' => 'VictoriousMarket/1.0 (support@victoriousmarket.com.ng)'])
                    ->get($nomUrl);

                if ($res->successful()) {
                    $items = $res->json() ?? [];
                    foreach ($items as $item) {
                        $addr = $item['address'] ?? [];
                        $name = $item['name'] ?? ($addr['road'] ?? ($item['display_name'] ?? ''));
                        $city = $addr['city'] ?? ($addr['county'] ?? ($addr['state'] ?? 'Nigeria'));
                        $state = $addr['state'] ?? '';
                        $formatted = $item['display_name'] ?? sprintf('%s, %s, %s', $name, $city, $state);
                        $lat = (float) ($item['lat'] ?? 0);
                        $lon = (float) ($item['lon'] ?? 0);
                        $placeId = 'zero_' . base64_encode("{$lat},{$lon}|{$formatted}");

                        $results[] = [
                            'placePrediction' => [
                                'place' => $formatted,
                                'placeId' => $placeId,
                                'text' => ['text' => $formatted],
                                'structuredFormat' => [
                                    'mainText' => ['text' => $name],
                                    'secondaryText' => ['text' => trim("{$city}, {$state}", ', ')],
                                ],
                                'types' => ['geocode', 'street_address'],
                            ]
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('[AI] Nominatim search failed: ' . $e->getMessage());
            }

            // 2. Fallback to Photon if Nominatim returned empty
            if (empty($results)) {
                try {
                    $photonUrl = 'https://photon.komoot.io/api/?q=' . urlencode($clean . ', Nigeria') . '&limit=6';
                    $res = Http::timeout(5)
                        ->withHeaders(['User-Agent' => 'VictoriousMarket/1.0 (support@victoriousmarket.com.ng)'])
                        ->get($photonUrl);

                    if ($res->successful()) {
                        $features = $res->json('features') ?? [];
                        foreach ($features as $f) {
                            $props = $f['properties'] ?? [];
                            $coords = $f['geometry']['coordinates'] ?? [0, 0];
                            $name = $props['name'] ?? ($props['street'] ?? '');
                            if (empty($name)) continue;

                            $city = $props['city'] ?? ($props['county'] ?? '');
                            $state = $props['state'] ?? '';
                            $formatted = sprintf('%s, %s, %s', $name, $city, $state);
                            $lat = (float) ($coords[1] ?? 0);
                            $lon = (float) ($coords[0] ?? 0);
                            $placeId = 'zero_' . base64_encode("{$lat},{$lon}|{$formatted}");

                            $results[] = [
                                'placePrediction' => [
                                    'place' => $formatted,
                                    'placeId' => $placeId,
                                    'text' => ['text' => $formatted],
                                    'structuredFormat' => [
                                        'mainText' => ['text' => $name],
                                        'secondaryText' => ['text' => trim("{$city}, {$state}", ', ')],
                                    ],
                                    'types' => ['geocode', 'street_address'],
                                ]
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('[AI] Photon fallback search failed: ' . $e->getMessage());
                }
            }

            return $results;
        });

        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * Calculate Haversine distance in meters between two lat/lng points.
     */
    protected function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return (int) round($earthRadius * $c);
    }
}
