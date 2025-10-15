<?php

declare(strict_types=1);

namespace RagSystem\UI\Http\Controller;

use RagSystem\Application\Service\AuthService;
use RagSystem\Application\Service\AuthorizationService;
use RagSystem\Infrastructure\Http\Request;
use RagSystem\Infrastructure\Http\Response;

class AuthController
{
    public function __construct(
        private AuthService $authService,
        private AuthorizationService $authorizationService
    ) {}

    /**
     * Обрабатывает запрос на вход в систему
     */
    public function login(Request $request): Response
    {
        $data = $request->getBody();

        if (!$data || !isset($data['username']) || !isset($data['password'])) {
            return Response::json([
                'error' => 'Invalid request',
                'message' => 'Username and password are required',
                'code' => 400
            ], 400);
        }

        $username = trim($data['username']);
        $password = $data['password'];

        if (empty($username) || empty($password)) {
            return Response::json([
                'error' => 'Invalid credentials',
                'message' => 'Username and password cannot be empty',
                'code' => 400
            ], 400);
        }

        $ipAddress = $this->getClientIp($request);
        $userAgent = $request->getHeader('User-Agent');

        $session = $this->authService->authenticate($username, $password, $ipAddress, $userAgent);

        if (!$session) {
            return Response::json([
                'error' => 'Authentication failed',
                'message' => 'Invalid username or password',
                'code' => 401
            ], 401);
        }

        $user = $this->authService->validateSession($session->getSessionToken());
        
        if (!$user) {
            return Response::json([
                'error' => 'Authentication failed',
                'message' => 'Unable to validate user session',
                'code' => 401
            ], 401);
        }
        
        $permissions = $this->authorizationService->getUserPermissions($user);
        $actions = $this->authorizationService->getAvailableActions($user);

        return Response::json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'session_token' => $session->getSessionToken(),
                'user' => [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                    'is_active' => $user->isActive(),
                    'last_login_at' => $user->getLastLoginAt()?->format('Y-m-d H:i:s'),
                ],
                'permissions' => $permissions,
                'available_actions' => $actions,
                'session_expires_at' => $session->getExpiresAt()->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * Обрабатывает запрос на выход из системы
     */
    public function logout(Request $request): Response
    {
        $sessionToken = $this->extractSessionToken($request);

        if (!$sessionToken) {
            return Response::json([
                'error' => 'Session token required',
                'message' => 'Session token is required for logout',
                'code' => 400
            ], 400);
        }

        $success = $this->authService->logout($sessionToken);

        if (!$success) {
            return Response::json([
                'error' => 'Logout failed',
                'message' => 'Invalid session token',
                'code' => 400
            ], 400);
        }

        return Response::json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }

    /**
     * Проверяет статус авторизации пользователя
     */
    public function me(Request $request): Response
    {
        $sessionToken = $this->extractSessionToken($request);
        
        if (!$sessionToken) {
            return Response::json([
                'error' => 'Unauthorized',
                'message' => 'Session token required',
                'code' => 401
            ], 401);
        }

        $user = $this->authService->validateSession($sessionToken);

        if (!$user) {
            return Response::json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or expired session',
                'code' => 401
            ], 401);
        }

        $permissions = $this->authorizationService->getUserPermissions($user);
        $actions = $this->authorizationService->getAvailableActions($user);

        return Response::json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                    'is_active' => $user->isActive(),
                    'last_login_at' => $user->getLastLoginAt()?->format('Y-m-d H:i:s'),
                ],
                'permissions' => $permissions,
                'available_actions' => $actions,
            ]
        ]);
    }

    /**
     * Продлевает сессию пользователя
     */
    public function extendSession(Request $request): Response
    {
        $sessionToken = $this->extractSessionToken($request);

        if (!$sessionToken) {
            return Response::json([
                'error' => 'Session token required',
                'message' => 'Session token is required',
                'code' => 400
            ], 400);
        }

        $data = $request->getBody();
        $additionalSeconds = $data['extend_by'] ?? 3600; // По дефолту 1 час

        $success = $this->authService->extendSession($sessionToken, (int) $additionalSeconds);

        if (!$success) {
            return Response::json([
                'error' => 'Session extension failed',
                'message' => 'Invalid or expired session',
                'code' => 400
            ], 400);
        }

        return Response::json([
            'success' => true,
            'message' => 'Session extended successfully'
        ]);
    }

    /**
     * Извлекает токен сессии из запроса
     */
    private function extractSessionToken(Request $request): ?string
    {
        $authHeader = $request->getHeader('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        $queryParams = $request->getQuery();
        if (isset($queryParams['session_token'])) {
            return $queryParams['session_token'];
        }

        return null;
    }

    /**
     * Получает IP адрес клиента
     */
    private function getClientIp(Request $request): ?string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            $ip = $request->getHeader($header);
            if ($ip) {
                $ip = explode(',', $ip)[0];
                $ip = trim($ip);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? null;
    }
}
