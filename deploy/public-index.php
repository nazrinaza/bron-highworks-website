<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Keep private application files outside public_html on either supported cPanel layout.
$applicationPaths = [
    dirname(__DIR__).'/bron',
    dirname(__DIR__, 3).'/bron',
];
$applicationPath = null;

foreach ($applicationPaths as $candidate) {
    if (is_file($candidate.'/bootstrap/app.php')) {
        $applicationPath = $candidate;
        break;
    }
}

if ($applicationPath === null) {
    http_response_code(503);
    exit('BRON application files are unavailable.');
}

if (file_exists($maintenance = $applicationPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $applicationPath.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $applicationPath.'/bootstrap/app.php';
$app->usePublicPath(__DIR__);
$app->handleRequest(Request::capture());
