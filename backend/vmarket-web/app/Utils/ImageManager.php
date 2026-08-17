<?php

namespace App\Utils;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ImageManager
{
    public static function upload(string $dir, string $format, $image, $file_type = 'image'): string
    {
        $storage = config('filesystems.disks.default') ?? 'public';
        if ($image != null) {
            if (!Storage::disk($storage)->exists($dir)) {
                Storage::disk($storage)->makeDirectory($dir);
            }

            $originalExtension = strtolower(method_exists($image, 'getClientOriginalExtension') ? $image->getClientOriginalExtension() : 'png');

            if (in_array($originalExtension, ['gif', 'svg'])) {
                $imageName = Carbon::now()->toDateString() . "-" . uniqid() . "." . $originalExtension;
                Storage::disk($storage)->put($dir . $imageName, file_get_contents($image));
            } else {
                try {
                    $imageWebp = Image::make($image);
                    
                    // If image exceeds 2MB, automatically downscale it to max 1200px
                    if (method_exists($image, 'getSize') && $image->getSize() > 2 * 1024 * 1024) {
                        $imageWebp->resize(1200, 1200, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                    }

                    // Fallback to jpg/png if GD webp support is unavailable on the server
                    if ($format === 'webp' && (!function_exists('imagetypes') || !(imagetypes() & IMG_WEBP))) {
                        $format = ($originalExtension === 'png') ? 'png' : 'jpg';
                    }

                    $imageEncoded = $imageWebp->encode($format, 85);
                    $imageName = Carbon::now()->toDateString() . "-" . uniqid() . "." . $format;
                    Storage::disk($storage)->put($dir . $imageName, (string)$imageEncoded);
                    $imageWebp->destroy();
                } catch (\Exception $e) {
                    // Fail-safe direct stream copy if Intervention encounters an issue
                    $safeExt = !empty($originalExtension) ? $originalExtension : 'png';
                    $imageName = Carbon::now()->toDateString() . "-" . uniqid() . "." . $safeExt;
                    Storage::disk($storage)->put($dir . $imageName, file_get_contents($image));
                }
            }
        } else {
            $imageName = 'def.webp';
        }
        return $imageName;
    }

    public static function file_upload(string $dir, string $format, $file = null): string
    {
        $storage = config('filesystems.disks.default') ?? 'public';
        if ($file != null) {
            $fileName = Carbon::now()->toDateString() . "-" . uniqid() . "." . $format;
            if (!Storage::disk($storage)->exists($dir)) {
                Storage::disk($storage)->makeDirectory($dir);
            }
            Storage::disk($storage)->put($dir . $fileName, file_get_contents($file));
        } else {
            $fileName = 'def.png';
        }

        return $fileName;
    }

    public static function update(string $dir, $old_image, string $format, $image, $file_type = 'image'): string
    {
        if (self::checkFileExists(filePath: $dir.$old_image)['status']) {
            Storage::disk(self::checkFileExists(filePath: $dir . $old_image)['disk'])->delete($dir . $old_image);
        }

        return $file_type == 'file' ? ImageManager::file_upload($dir, $format, $image) : ImageManager::upload($dir, $format, $image);
    }

    public static function delete($full_path): array
    {
        if (self::checkFileExists(filePath: $full_path)['status']) {
            Storage::disk(self::checkFileExists(filePath: $full_path)['disk'])->delete($full_path);
        }
        return [
            'success' => 1,
            'message' => 'Removed successfully !'
        ];

    }
    public static function checkFileExists(string $filePath): array
    {
        if (Storage::disk('public')->exists($filePath)) {
            return [
                'status' => true,
                'disk' => 'public'
            ];
        } elseif (config('filesystems.disks.default') == 's3' && Storage::disk('s3')->exists($filePath)) {
            return [
                'status' => true,
                'disk' => 's3'
            ];
        } else {
            return [
                'status' => false,
                'disk' => config('filesystems.disks.default') ?? 'public'
            ];
        }
    }

}
