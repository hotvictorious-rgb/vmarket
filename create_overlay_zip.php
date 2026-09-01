<?php

/**
 * [AI] Generator: Complete Safe Live Overlay Update Package (vmarket_live_overlay_update.zip)
 * Packages 100% of the updated backend application while preserving .env, storage/, and vendor/
 */

$sourceDir = __DIR__ . '/backend/vmarket-web';
$zipFile = __DIR__ . '/vmarket_live_overlay_update.zip';

if (file_exists($zipFile)) {
    unlink($zipFile);
}

$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("Cannot create zip archive.\n");
}

$foldersToInclude = [
    'app',
    'bootstrap',
    'config',
    'database',
    'Modules',
    'resources',
    'routes',
    'public/assets',
];

$filesToInclude = [
    'composer.json',
    'composer.lock',
    'artisan',
    'public/deploy.php',
];

echo "Packaging update overlay...\n";

foreach ($foldersToInclude as $folder) {
    $folderPath = $sourceDir . '/' . $folder;
    if (!is_dir($folderPath)) continue;

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($folderPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($files as $file) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($sourceDir) + 1);
        $relativePath = str_replace('\\', '/', $relativePath);

        if ($file->isDir()) {
            $zip->addEmptyDir($relativePath);
        } else {
            $zip->addFile($filePath, $relativePath);
        }
    }
}

foreach ($filesToInclude as $f) {
    $fullPath = $sourceDir . '/' . $f;
    if (file_exists($fullPath)) {
        $zip->addFile($fullPath, $f);
    }
}

$zip->close();

echo "========================================================================================\n";
echo "🎉 SUCCESS: vmarket_live_overlay_update.zip CREATED (" . number_format(filesize($zipFile)) . " bytes)\n";
echo "========================================================================================\n";
