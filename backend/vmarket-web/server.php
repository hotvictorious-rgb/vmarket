<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

if ($uri !== '/') {
    $publicFile = __DIR__ . '/public' . $uri;
    if (file_exists($publicFile) && !is_dir($publicFile)) {
        // If document root is already public, let PHP's internal server handle it
        if (realpath($_SERVER['DOCUMENT_ROOT'] ?? '') === realpath(__DIR__ . '/public')) {
            return false;
        }

        // Otherwise serve directly with appropriate MIME type
        $mimeTypes = [
            'css'   => 'text/css',
            'js'    => 'application/javascript',
            'json'  => 'application/json',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'svg'   => 'image/svg+xml',
            'ico'   => 'image/x-icon',
            'webp'  => 'image/webp',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
            'eot'   => 'application/vnd.ms-fontobject',
            'otf'   => 'font/otf',
            'pdf'   => 'application/pdf',
            'mp3'   => 'audio/mpeg',
            'wav'   => 'audio/wav',
        ];
        $ext = strtolower(pathinfo($publicFile, PATHINFO_EXTENSION));
        $mime = $mimeTypes[$ext] ?? (function_exists('mime_content_type') ? mime_content_type($publicFile) : 'application/octet-stream');
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($publicFile));
        header('Cache-Control: public, max-age=86400');
        readfile($publicFile);
        exit;
    }

    if (file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
        return false;
    }
}

require_once __DIR__.'/public/index.php';
