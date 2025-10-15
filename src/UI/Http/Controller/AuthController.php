<?php

declare(strict_types=1);

namespace RagSystem\UI\Http\Controller;

use RagSystem\Application\Service\AuthService;
use RagSystem\Application\Service\AuthorizationService;
use RagSystem\Application\DTO\Auth\LoginRequestDTO;
use RagSystem\Application\DTO\Auth\LogoutRequestDTO;
use RagSystem\Application\DTO\Response\ApiResponseDTO;
use RagSystem\Application\Factory\ApiResponseFactory;
use RagSystem\Application\Validation\Auth\LoginRequestValidator;
use RagSystem\Application\Validation\Auth\LogoutRequestValidator;
use RagSystem\Infrastructure\Http\Request;
use RagSystem\Infrastructure\Http\Response;
use InvalidArgumentException;

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
        try {
            $dto = LoginRequestDTO::fromArray($request->getBody());
            $validator = new LoginRequestValidator();
            $validationResult = $validator->validate($dto);
            
            if (!$validationResult->isValid()) {
                $responseDto = ApiResponseFactory::error('Validation failed', $validationResult->getErrors(), 400);
                return Response::json($responseDto->toArray(), 400);
            }
            
            $ipAddress = $this->getClientIp($request);
            $userAgent = $request->getHeader('User-Agent');

            $session = $this->authService->authenticate($dto->username, $dto->password, $ipAddress, $userAgent);

            if (!$session) {
                $responseDto = ApiResponseFactory::error('Authentication failed', ['credentials' => 'Invalid username or password'], 401);
                return Response::json($responseDto->toArray(), 401);
            }

            $user = $this->authService->validateSession($session->getSessionToken());
            
            if (!$user) {
                $responseDto = ApiResponseFactory::error('Authentication failed', ['session' => 'Unable to validate user session'], 401);
                return Response::json($responseDto->toArray(), 401);
            }
            
            $permissions = $this->authorizationService->getUserPermissions($user);
            $actions = $this->authorizationService->getAvailableActions($user);

            $responseDto = ApiResponseFactory::success('Login successful', [
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
            ]);

            return Response::json($responseDto->toArray());
            
        } catch (InvalidArgumentException $e) {
            $responseDto = ApiResponseFactory::error('Invalid request data', ['request' => $e->getMessage()], 400);
            return Response::json($responseDto->toArray(), 400);
        }
    }

    /**
     * Обрабатывает запрос на выход из системы
     */
    public function logout(Request $request): Response
    {
        try {
            $sessionToken = $this->extractSessionToken($request);
            
            if (!$sessionToken) {
                $responseDto = ApiResponseFactory::error('Session token required', ['session_token' => 'Session token is required for logout'], 400);
                return Response::json($responseDto->toArray(), 400);
            }

            $success = $this->authService->logout($sessionToken);

            if (!$success) {
                $responseDto = ApiResponseFactory::error('Logout failed', ['session_token' => 'Invalid session token'], 400);
                return Response::json($responseDto->toArray(), 400);
            }

            $responseDto = ApiResponseFactory::success('Logout successful');
            return Response::json($responseDto->toArray());
            
        } catch (InvalidArgumentException $e) {
            $responseDto = ApiResponseFactory::error('Invalid request data', ['request' => $e->getMessage()], 400);
            return Response::json($responseDto->toArray(), 400);
        }
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
