<?php

declare(strict_types=1);

use RagSystem\UI\Http\Controller\DocumentController;
use RagSystem\UI\Http\Controller\QueryController;
use RagSystem\UI\Http\Controller\HealthController;
use RagSystem\UI\Http\Controller\AuthController;
use RagSystem\Infrastructure\Http\Router;
use RagSystem\Infrastructure\Http\Middleware\AuthMiddleware;

/**
 * @var Router $router
 */

// Health check (public)
$router->get('/health', [HealthController::class, 'check']);

// Auth routes (public)
$router->group('/api/v1/auth', function ($router) {
    $router->post('/login', [AuthController::class, 'login']);
    $router->post('/logout', [AuthController::class, 'logout']);
    $router->get('/me', [AuthController::class, 'me']);
    $router->post('/extend', [AuthController::class, 'extendSession']);
});

// Protected API routes
$router->group('/api/v1', function ($router) {
    // Document management (protected)
    $router->get('/documents', [DocumentController::class, 'index']);
    $router->post('/documents', [DocumentController::class, 'store']);
    $router->get('/documents/{id}', [DocumentController::class, 'show']);
    $router->put('/documents/{id}', [DocumentController::class, 'update']);
    $router->delete('/documents/{id}', [DocumentController::class, 'destroy']);

    // Document upload (protected)
    $router->post('/documents/upload', [DocumentController::class, 'upload']);
    
    // Document processing status (protected)
    $router->get('/documents/{id}/processing-status', [DocumentController::class, 'processingStatus']);

    // Query and retrieval (protected)
    $router->post('/query', [QueryController::class, 'query']);
    $router->post('/search', [QueryController::class, 'search']);
    $router->get('/queries', [QueryController::class, 'history']);
    $router->post('/queries/similar', [QueryController::class, 'similar']);
    $router->patch('/query/{id}', [QueryController::class, 'updateResponseTime']);
}, [AuthMiddleware::class]);
