<?php

namespace App\Services;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct()
    {
        $this->clientId = $this->getSetting('youtube_client_id') ?? '';
        $this->clientSecret = $this->getSetting('youtube_client_secret') ?? '';
        $this->redirectUri = route('admin.youtube.callback');
    }

    /**
     * Get setting value from business_settings table.
     */
    private function getSetting(string $key): ?string
    {
        $setting = BusinessSetting::where('type', $key)->first();
        return $setting ? $setting->value : null;
    }

    /**
     * Save setting value to business_settings table.
     */
    private function saveSetting(string $key, ?string $value): void
    {
        BusinessSetting::updateOrCreate(
            ['type' => $key],
            ['value' => $value]
        );
    }

    /**
     * Check if client credentials are set.
     */
    public function hasCredentials(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Generate Google Authorization URL.
     */
    public function getAuthUrl(): string
    {
        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/youtube.upload',
            'access_type' => 'offline',
            'prompt' => 'consent'
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $query;
    }

    /**
     * Exchange auth code for access & refresh tokens.
     */
    public function handleCallback(string $code): bool
    {
        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUri,
                'grant_type' => 'authorization_code',
                'code' => $code
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->saveSetting('youtube_access_token', $data['access_token'] ?? null);
                if (isset($data['refresh_token'])) {
                    $this->saveSetting('youtube_refresh_token', $data['refresh_token']);
                }
                return true;
            }

            Log::error('YouTube OAuth token exchange failed: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('YouTube OAuth Callback Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a fresh access token (using the refresh token).
     */
    public function getAccessToken(): ?string
    {
        $refreshToken = $this->getSetting('youtube_refresh_token');
        if (!$refreshToken) {
            return null;
        }

        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $accessToken = $data['access_token'] ?? null;
                $this->saveSetting('youtube_access_token', $accessToken);
                return $accessToken;
            }

            Log::error('YouTube token refresh failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('YouTube Token Refresh Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Upload a video file to YouTube.
     * Returns the video watch URL or null on failure.
     */
    public function uploadVideo(string $filePath, string $title, string $description = ''): ?string
    {
        if (!file_exists($filePath)) {
            Log::error('YouTube Upload: File does not exist at ' . $filePath);
            return null;
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            Log::error('YouTube Upload: No active access token available.');
            return null;
        }

        try {
            $fileSize = filesize($filePath);
            $mimeType = mime_content_type($filePath);

            // Step 1: Initiate resumable upload session
            $metadataResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json; charset=UTF-8',
                'X-Upload-Content-Length' => $fileSize,
                'X-Upload-Content-Type' => $mimeType,
            ])->post('https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status', [
                'snippet' => [
                    'title' => substr($title, 0, 99), // YouTube limit: 100 chars
                    'description' => $description,
                    'categoryId' => '22' // People & Blogs
                ],
                'status' => [
                    'privacyStatus' => 'unlisted' // Unlisted by default
                ]
            ]);

            if (!$metadataResponse->successful()) {
                Log::error('YouTube Upload Session initiation failed: ' . $metadataResponse->body());
                return null;
            }

            $uploadUrl = $metadataResponse->header('Location');
            if (!$uploadUrl) {
                Log::error('YouTube Upload: Location header missing from session initiation.');
                return null;
            }

            // Step 2: Stream the video file to the session URL in one single PUT request
            $stream = fopen($filePath, 'r');
            $uploadResponse = Http::withHeaders([
                'Content-Length' => $fileSize,
                'Content-Type' => $mimeType,
            ])->withBody($stream, $mimeType)
              ->put($uploadUrl);

            if (is_resource($stream)) {
                fclose($stream);
            }

            if ($uploadResponse->successful()) {
                $data = $uploadResponse->json();
                $videoId = $data['id'] ?? null;
                if ($videoId) {
                    return 'https://www.youtube.com/embed/' . $videoId;
                }
            }

            Log::error('YouTube Upload PUT request failed: ' . $uploadResponse->body());
            return null;
        } catch (\Exception $e) {
            Log::error('YouTube Upload Exception: ' . $e->getMessage());
            return null;
        }
    }
}
