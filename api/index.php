<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Autoloader
require __DIR__ . '/../vendor/autoload.php';

// Prepare writable directories in /tmp for Vercel's read-only serverless filesystem
$tmpDirs = [
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
    '/tmp/bootstrap/cache',
];

foreach ($tmpDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

putenv('VIEW_COMPILED_PATH=/tmp/storage/framework/views');

/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->useStoragePath('/tmp/storage');

$app->handleRequest(Request::capture());
