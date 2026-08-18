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

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$requestPath = rawurldecode($requestPath);
$basePath = '/forums';

if ($requestPath === $basePath) {
    $relativePath = '';
} elseif (str_starts_with($requestPath, $basePath . '/')) {
    $relativePath = ltrim(substr($requestPath, strlen($basePath)), '/');
} else {
    $relativePath = ltrim($requestPath, '/');
}

$publicRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'public');
$candidatePath = $publicRoot === false
    ? false
    : realpath($publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
$allowedTypes = [
    'css' => 'text/css; charset=UTF-8',
    'gif' => 'image/gif',
    'ico' => 'image/x-icon',
    'jpeg' => 'image/jpeg',
    'jpg' => 'image/jpeg',
    'js' => 'application/javascript; charset=UTF-8',
    'json' => 'application/json; charset=UTF-8',
    'map' => 'application/json; charset=UTF-8',
    'png' => 'image/png',
    'svg' => 'image/svg+xml',
    'ttf' => 'font/ttf',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
];

header('X-Content-Type-Options: nosniff');

if (
    $publicRoot !== false
    && $candidatePath !== false
    && is_file($candidatePath)
    && str_starts_with($candidatePath, $publicRoot . DIRECTORY_SEPARATOR)
) {
    $extension = strtolower(pathinfo($candidatePath, PATHINFO_EXTENSION));

    if (isset($allowedTypes[$extension])) {
        header('Content-Type: ' . $allowedTypes[$extension]);
        readfile($candidatePath);
        return;
    }
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.php';
