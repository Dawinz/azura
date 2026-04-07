<?php
/**
 * PHP built-in server router: forwards all non-file requests to index.php
 * so that /v1/category/list works without index.php in the URL.
 */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = __DIR__ . $uri;
if ($uri !== '/' && $uri !== '' && file_exists($path) && !is_dir($path)) {
    return false; // let PHP serve the file
}
require __DIR__ . '/index.php';
