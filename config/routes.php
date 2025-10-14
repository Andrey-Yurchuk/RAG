<?php

declare(strict_types=1);

use RagSystem\UI\Http\Controller\DocumentController;
use RagSystem\UI\Http\Controller\QueryController;
use RagSystem\UI\Http\Controller\HealthController;
use RagSystem\Infrastructure\Http\Router;

/**
 * @var Router $router
 */

// Health check
$router->get('/health', [HealthController::class, 'check']);

// API routes
$router->group('/api/v1', function ($router) {
    // Document management
    $router->get('/documents', [DocumentController::class, 'index']);
    $router->post('/documents', [DocumentController::class, 'store']);
    $router->get('/documents/{id}', [DocumentController::class, 'show']);
    $router->put('/documents/{id}', [DocumentController::class, 'update']);
    $router->delete('/documents/{id}', [DocumentController::class, 'destroy']);

    // Document upload
    $router->post('/documents/upload', [DocumentController::class, 'upload']);

    // Query and retrieval
    $router->post('/query', [QueryController::class, 'query']);
    $router->post('/search', [QueryController::class, 'search']);
    $router->get('/queries', [QueryController::class, 'history']);
    $router->post('/queries/similar', [QueryController::class, 'similar']);
    $router->patch('/query/{id}', [QueryController::class, 'updateResponseTime']);
});