<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReceiptUploadService
{
    protected const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/jpg',
    ];

    protected const MAX_FILE_SIZE_BYTES = 5 * 1024 * 1024; // 5MB

    /**
     * [AI] Validates, rate-limits, sanitizes, and stores a transfer receipt securely.
     * Re-encodes the image into WebP format, stripping EXIF metadata & embedded payloads.
     */
    public static function processAndStoreReceipt(UploadedFile|string $file, string $identifier): array
    {
        // 1. Rate Limiting Check (Max 3 uploads per 10 minutes)
        $rateLimitKey = 'receipt_upload_limit_' . md5($identifier);
        $attempts = Cache::get($rateLimitKey, 0);
        if ($attempts >= 3) {
            return [
                'status' => false,
                'message' => 'Upload rate limit exceeded. Please wait 10 minutes before uploading another receipt.',
            ];
        }

        try {
            // Handle binary string or UploadedFile
            if ($file instanceof UploadedFile) {
                if ($file->getSize() > self::MAX_FILE_SIZE_BYTES) {
                    return [
                        'status' => false,
                        'message' => 'File size exceeds maximum limit of 5MB.',
                    ];
                }

                $mimeType = $file->getMimeType();
                if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
                    return [
                        'status' => false,
                        'message' => 'Invalid file format. Only JPEG, PNG, and WebP images are allowed.',
                    ];
                }

                $rawContent = file_get_contents($file->getRealPath());
            } elseif (is_string($file)) {
                // If base64 or file path
                if (str_starts_with($file, 'data:image')) {
                    $base64Data = substr($file, strpos($file, ',') + 1);
                    $rawContent = base64_decode($base64Data);
                } elseif (file_exists($file)) {
                    $rawContent = file_get_contents($file);
                } else {
                    $rawContent = $file;
                }
            } else {
                return ['status' => false, 'message' => 'Invalid file source provided.'];
            }

            if (empty($rawContent)) {
                return ['status' => false, 'message' => 'Uploaded file is empty.'];
            }

            // 2. Re-encode Image to WebP & Strip EXIF Payloads
            $imageResource = @imagecreatefromstring($rawContent);
            if (!$imageResource) {
                return [
                    'status' => false,
                    'message' => 'Uploaded file is corrupt or is not a valid image.',
                ];
            }

            // Generate clean unique filename
            $filename = 'receipt_' . Str::random(24) . '_' . time() . '.webp';
            $relativeDir = 'receipts/' . date('Y/m');
            $fullDir = storage_path('app/public/' . $relativeDir);

            if (!file_exists($fullDir)) {
                mkdir($fullDir, 0755, true);
            }

            $destinationPath = $fullDir . '/' . $filename;

            // Preserve alpha transparency if PNG/WebP
            imagealphablending($imageResource, false);
            imagesavealpha($imageResource, true);

            // Export as clean WebP with quality 85
            imagewebp($imageResource, $destinationPath, 85);
            imagedestroy($imageResource);

            $storedRelativePath = $relativeDir . '/' . $filename;

            // Increment rate limiter (10 min TTL)
            Cache::put($rateLimitKey, $attempts + 1, now()->addMinutes(10));

            return [
                'status' => true,
                'file_path' => $storedRelativePath,
                'full_path' => $destinationPath,
                'url' => asset('storage/' . $storedRelativePath),
            ];

        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Receipt processing failed: ' . $e->getMessage(),
            ];
        }
    }
}
