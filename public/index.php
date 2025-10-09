<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use RagSystem\Infrastructure\DependencyInjection\Container;
use RagSystem\Infrastructure\DependencyInjection\ServiceProvider;
use RagSystem\Infrastructure\Http\Router;
use RagSystem\Infrastructure\Http\Request;
use RagSystem\Infrastructure\Http\Response;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

error_reporting(E_ALL);
ini_set('display_errors', $_ENV['APP_DEBUG'] === 'true' ? '1' : '0');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// Debug logging for file upload
$logFile = __DIR__ . '/../storage/logs/request_debug.log';
file_put_contents($logFile,
    '[' . date('Y-m-d H:i:s') . '] ' .
    $_SERVER['REQUEST_METHOD'] . ' ' . $requestUri . ' ' .
    'Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'none') . ' ' .
    'Is-API: ' . (str_starts_with($requestPath, '/api/') ? 'yes' : 'no') . "\n",
    FILE_APPEND
);

// Check if this is an API request
if (str_starts_with($requestPath, '/api/') || $requestPath === '/health') {
    $container = new Container();
    ServiceProvider::register($container);

    $router = new Router($container);
    require_once __DIR__ . '/../config/routes.php';

    try {
        $request = Request::fromGlobals();
        $response = $router->handle($request);
    } catch (\Throwable $e) {
        if (isset($container) && $container->has(LoggerInterface::class)) {
            $logger = $container->get(LoggerInterface::class);
            $logger->error('Application error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        $response = Response::internalServerError('Internal server error');
    }

    // Send API response
    http_response_code($response->getStatusCode());

    foreach ($response->getHeaders() as $name => $value) {
        header("{$name}: {$value}");
    }

    echo $response->getBody();
} else {
    $requestPath = trim($requestPath, '/');

    if ($requestPath && file_exists(__DIR__ . '/' . $requestPath)) {
        $file = __DIR__ . '/' . $requestPath;
        $mimeType = mime_content_type($file);

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }

    if (file_exists(__DIR__ . '/index.html')) {
        header('Content-Type: text/html; charset=utf-8');
        readfile(__DIR__ . '/index.html');
    } else {
        header('HTTP/1.1 404 Not Found');
        echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 Not Found</h1><p>The requested resource was not found.</p></body></html>';
    }
}