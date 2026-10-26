<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Manila');
session_start();

use App\Core\Request;

ini_set('display_errors', '1');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Two autoload roots: API (App\) and admin (Admin\)
$roots = [
    'App\\'   => __DIR__ . '/api/app/',
    'Admin\\' => __DIR__ . '/admin/',
];

spl_autoload_register(function (string $class) use ($roots): void {
    foreach ($roots as $prefix => $root) {
        if (!str_starts_with($class, $prefix)) continue;
        $relative = substr($class, strlen($prefix));
        $file = $root . str_replace('\\', '/', $relative) . '.php';
        if (is_file($file)) { require $file; return; }
    }
});

// Composer autoloader (PHPMailer, shared with the API)
$composer = __DIR__ . '/api/vendor/autoload.php';
if (is_file($composer)) require $composer;

$router = new Admin\Core\Router();
require __DIR__ . '/routes.php';
$router->dispatch(Request::capture());