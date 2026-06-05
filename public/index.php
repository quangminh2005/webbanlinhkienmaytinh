<?php

declare(strict_types=1);

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Saigon');

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
define('BASE_PATH', $scriptDir === '/' ? '' : rtrim($scriptDir, '/'));

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($requestPath === BASE_PATH . '/healthz') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

session_start();

require __DIR__ . '/../app/core/helpers.php';

spl_autoload_register(function ($class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    $file = preg_replace('#/(controllers|models|core)/#', '/$1/', $file);
    $file = str_replace('/Controllers/', '/controllers/', $file);
    $file = str_replace('/Models/', '/models/', $file);
    $file = str_replace('/Core/', '/core/', $file);

    if (file_exists($file)) {
        require $file;
    }
});

$router = new App\Core\Router();
$uri = $_SERVER['REQUEST_URI'];
if (BASE_PATH !== '' && str_starts_with($uri, BASE_PATH)) {
    $uri = substr($uri, strlen(BASE_PATH));
    if ($uri === '') {
        $uri = '/';
    }
}

$router->dispatch($uri, $_SERVER['REQUEST_METHOD']);

