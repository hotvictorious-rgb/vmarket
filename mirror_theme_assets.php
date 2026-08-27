<?php

/**
 * Mirror resources/themes into public/themes
 */

$source = __DIR__ . '/backend/vmarket-web/resources/themes';
$dest = __DIR__ . '/backend/vmarket-web/public/themes';

function copyDir($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst, 0777, true);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                copyDir($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

copyDir($source, $dest);

echo "Successfully mirrored resources/themes to public/themes!\n";
