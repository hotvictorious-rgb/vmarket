<?php
/**
 * Victorious Market Image Optimizer
 * Resizes and compresses existing images to resolve PageSpeed Insights warnings.
 */

// Target folders relative to this script
$dirs = [
    __DIR__ . '/storage/app/public/shop' => 300,            // Shop logos (display size ~137x205) -> max 300px
    __DIR__ . '/storage/app/public/company' => 800,         // Company banners/logos -> max 800px
    __DIR__ . '/storage/app/public/product/thumbnail' => 500, // Product thumbnails -> max 500px
    __DIR__ . '/resources/themes/theme_aster/public/assets/img/placeholder' => 600, // Placeholders -> max 600px
    __DIR__ . '/public/assets/front-end/img/placeholder' => 600,
    __DIR__ . '/public/assets/back-end/img/placeholder' => 600,
    __DIR__ . '/public/assets/new/back-end/img/placeholder' => 600,
];

echo "=============================================\n";
echo "Starting Victorious Market Image Optimizer\n";
echo "=============================================\n\n";

$totalSaved = 0;
$totalFiles = 0;

foreach ($dirs as $dir => $maxDim) {
    if (!is_dir($dir)) {
        echo "Directory not found, skipping: $dir\n";
        continue;
    }

    echo "Scanning directory: $dir (Max Dimension: {$maxDim}px)\n";
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    
    foreach ($files as $file) {
        if ($file->isDir()) {
            continue;
        }

        $path = $file->getRealPath();
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
            continue;
        }

        $origSize = filesize($path);
        if ($origSize <= 15 * 1024) { // Skip files under 15KB
            continue;
        }

        // Custom override for specific placeholders
        $currentMaxDim = $maxDim;
        if (basename($path) === 'shop.png') {
            $currentMaxDim = 200;
        }

        $imgData = @file_get_contents($path);
        if (!$imgData) {
            continue;
        }

        $src = @imagecreatefromstring($imgData);
        if (!$src) {
            echo "  [Error] Failed to load image: " . basename($path) . "\n";
            continue;
        }

        $width = imagesx($src);
        $height = imagesy($src);

        $needsResize = ($width > $currentMaxDim || $height > $currentMaxDim);
        
        if ($needsResize) {
            // Calculate new dimensions preserving aspect ratio
            if ($width > $height) {
                $newWidth = $currentMaxDim;
                $newHeight = (int)($height * ($currentMaxDim / $width));
            } else {
                $newHeight = $currentMaxDim;
                $newWidth = (int)($width * ($currentMaxDim / $height));
            }

            $dst = imagescale($src, $newWidth, $newHeight, IMG_BILINEAR_FIXED);
            if (!$dst) {
                $dst = $src;
            }
        } else {
            $dst = $src;
        }

        // Save back with compression
        ob_start();
        if ($ext === 'webp') {
            imagewebp($dst, null, 75);
        } elseif ($ext === 'png') {
            imagepng($dst, null, 8); // PNG compression 0-9
        } else {
            imagejpeg($dst, null, 75);
        }
        $compressedData = ob_get_clean();

        $newSize = strlen($compressedData);

        if ($newSize < $origSize) {
            file_put_contents($path, $compressedData);
            $saved = $origSize - $newSize;
            $totalSaved += $saved;
            $totalFiles++;
            
            $origKB = round($origSize / 1024, 1);
            $newKB = round($newSize / 1024, 1);
            $savedKB = round($saved / 1024, 1);
            $percentage = round(($saved / $origSize) * 100);

            echo "  [Optimized] " . basename($path) . "\n";
            echo "    Dimensions: {$width}x{$height} -> " . imagesx($dst) . "x" . imagesy($dst) . "\n";
            echo "    Size: {$origKB}KB -> {$newKB}KB (Saved: {$savedKB}KB, -{$percentage}%)\n";
        } else {
            // If optimization didn't make the file smaller, keep original
            echo "  [Skipped] " . basename($path) . " (No size savings)\n";
        }

        imagedestroy($src);
        if (isset($dst) && $dst !== $src) {
            imagedestroy($dst);
        }
    }
    echo "\n";
}

$totalSavedMB = round($totalSaved / (1024 * 1024), 2);
echo "=============================================\n";
echo "Optimization Completed!\n";
echo "Total files optimized: $totalFiles\n";
echo "Total disk space saved: {$totalSavedMB} MB\n";
echo "=============================================\n";
