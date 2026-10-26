<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');


ini_set('display_errors', '0');     // errors go to log only, not the body
ini_set('log_errors', '1');

use App\Core\Request;
use App\Core\Router;

ini_set('display_errors', '1');
error_reporting(E_ALL);

// Auto-load App\ classes from app/
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) require $file;
});

// Composer autoloader (for PHPMailer etc.)
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
}

// Preflight
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$router = new Router();
require __DIR__ . '/routes.php';

$router->dispatch(Request::capture());