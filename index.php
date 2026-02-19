<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * This file acts as a proxy for shared hosting where
 * the public directory cannot be set as the document root.
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// Remove the subdirectory prefix to get the relative path
$basePath = '/forums';
$relativePath = $uri;
if (str_starts_with($uri, $basePath)) {
    $relativePath = substr($uri, strlen($basePath));
}
if (empty($relativePath)) {
    $relativePath = '/';
}

// If the file exists in public/ directory, serve it directly
if ($relativePath !== '/' && file_exists(__DIR__ . '/public' . $relativePath)) {
    // For PHP files, include them
    if (str_ends_with($relativePath, '.php')) {
        require_once __DIR__ . '/public' . $relativePath;
        return;
    }
    // For static files, let the web server handle it by returning false
    // But since we're in PHP, we need to serve the file ourselves
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
        'map' => 'application/json',
    ];

    $ext = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
        readfile(__DIR__ . '/public' . $relativePath);
        return;
    }

    // Unknown type, just serve it
    readfile(__DIR__ . '/public' . $relativePath);
    return;
}

// Otherwise, forward to Laravel's front controller
require_once __DIR__ . '/public/index.php';
