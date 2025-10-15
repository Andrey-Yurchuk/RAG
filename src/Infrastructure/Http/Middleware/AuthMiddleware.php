<?php

declare(strict_types=1);

namespace RagSystem\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RagSystem\Application\Service\AuthService;
use RagSystem\Application\Service\AuthorizationService;
use RagSystem\Domain\Model\User;
use RagSystem\Infrastructure\Http\Response;
use GuzzleHttp\Psr7\Response as Psr7Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuthService $authService,
        private AuthorizationService $authorizationService
    ) {}

    /**
     * Обрабатывает HTTP запрос и проверяет авторизацию
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        if ($this->isPublicRoute($path)) {
            return $handler->handle($request);
        }

        $sessionToken = $this->extractSessionToken($request);

        if (!$sessionToken) {
            return $this->createUnauthorizedResponse('Session token required');
        }

        $user = $this->authService->validateSession($sessionToken);

        if (!$user) {
            return $this->createUnauthorizedResponse('Invalid or expired session');
        }

        if (!$this->authorizationService->canAccessApiEndpoint($user, $method, $path)) {
            return $this->createForbiddenResponse('Insufficient permissions');
        }

        $request = $request->withAttribute('user', $user);
        $request = $request->withAttribute('session_token', $sessionToken);

        return $handler->handle($request);
    }

    /**
     * Проверяет, является ли маршрут публичным
     */
    private function isPublicRoute(string $path): bool
    {
        $publicRoutes = [
            '/api/v1/auth/login',
            '/api/v1/auth/logout',
            '/api/v1/health',
            '/',
            '/assets/',
            '/favicon.ico',
        ];

        foreach ($publicRoutes as $route) {
            if (str_starts_with($path, $route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Извлекает токен сессии из запроса
     */
    private function extractSessionToken(ServerRequestInterface $request): ?string
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        $cookies = $request->getCookieParams();
        if (isset($cookies['session_token'])) {
            return $cookies['session_token'];
        }

        $queryParams = $request->getQueryParams();
        if (isset($queryParams['session_token'])) {
            return $queryParams['session_token'];
        }

        return null;
    }

    /**
     * Создает ответ об отсутствии авторизации
     */
    private function createUnauthorizedResponse(string $message): ResponseInterface
    {
        $response = Response::json([
            'error' => 'Unauthorized',
            'message' => $message,
            'code' => 401
        ], 401);
        
        return $this->convertToPsr7Response($response);
    }

    /**
     * Создает ответ о недостаточных правах
     */
    private function createForbiddenResponse(string $message): ResponseInterface
    {
        $response = Response::json([
            'error' => 'Forbidden',
            'message' => $message,
            'code' => 403
        ], 403);
        
        return $this->convertToPsr7Response($response);
    }

    /**
     * Преобразует Response в PSR-7 ResponseInterface
     */
    private function convertToPsr7Response(Response $response): ResponseInterface
    {
        return new Psr7Response(
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getBody()
        );
    }
}
