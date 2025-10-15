<?php

declare(strict_types=1);

namespace RagSystem\Application\Service;

use InvalidArgumentException;
use RagSystem\Domain\Model\User;
use RagSystem\Domain\Model\UserSession;
use RagSystem\Domain\Repository\UserRepositoryInterface;
use RagSystem\Domain\Repository\UserSessionRepositoryInterface;
use Psr\Log\LoggerInterface;

class AuthService
{
    private const int SESSION_LIFETIME = 86400; // 24 часа

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserSessionRepositoryInterface $sessionRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Аутентифицирует пользователя по username и паролю
     */
    public function authenticate(string $username, string $password, ?string $ipAddress = null, ?string $userAgent = null): ?UserSession
    {
        $user = $this->userRepository->findByUsername($username);

        if (!$user || !$user->isActive()) {
            $this->logger->warning('Authentication failed: user not found or inactive', ['username' => $username]);
            return null;
        }

        if (!$user->verifyPassword($password)) {
            $this->logger->warning('Authentication failed: invalid password', ['username' => $username]);
            return null;
        }

        // Обновляем время последнего входа
        $user->updateLastLogin();
        $this->userRepository->save($user);

        // Создаем новую сессию
        $session = UserSession::createForUser(
            $user->getId(),
            self::SESSION_LIFETIME,
            $ipAddress,
            $userAgent
        );

        $this->sessionRepository->save($session);

        $this->logger->info('User authenticated successfully', [
            'user_id' => $user->getId(),
            'username' => $username,
            'session_id' => $session->getId()
        ]);

        return $session;
    }

    /**
     * Проверяет валидность сессии по токену
     */
    public function validateSession(string $sessionToken): ?User
    {
        $session = $this->sessionRepository->findByToken($sessionToken);

        if (!$session || $session->isExpired()) {
            return null;
        }

        $user = $this->userRepository->findById($session->getUserId());

        if (!$user || !$user->isActive()) {
            // Удаляем сессию неактивного пользователя
            $this->sessionRepository->deleteByToken($sessionToken);
            return null;
        }

        return $user;
    }

    /**
     * Завершает сессию пользователя
     */
    public function logout(string $sessionToken): bool
    {
        $session = $this->sessionRepository->findByToken($sessionToken);

        if (!$session) {
            return false;
        }

        $this->sessionRepository->deleteByToken($sessionToken);

        $this->logger->info('User logged out', [
            'user_id' => $session->getUserId(),
            'session_id' => $session->getId()
        ]);

        return true;
    }

    /**
     * Завершает все сессии пользователя
     */
    public function logoutAllSessions(int $userId): int
    {
        $activeSessions = $this->sessionRepository->findActiveByUserId($userId);
        $count = count($activeSessions);

        $this->sessionRepository->deleteByUserId($userId);

        $this->logger->info('All user sessions terminated', [
            'user_id' => $userId,
            'sessions_count' => $count
        ]);

        return $count;
    }

    /**
     * Продлевает сессию
     */
    public function extendSession(string $sessionToken, int $additionalSeconds = 3600): bool
    {
        $session = $this->sessionRepository->findByToken($sessionToken);

        if (!$session || $session->isExpired()) {
            return false;
        }

        $session->extend($additionalSeconds);
        $this->sessionRepository->save($session);

        return true;
    }

    /**
     * Очищает истекшие сессии
     */
    public function cleanupExpiredSessions(): int
    {
        $deletedCount = $this->sessionRepository->deleteExpired();

        if ($deletedCount > 0) {
            $this->logger->info('Expired sessions cleaned up', ['count' => $deletedCount]);
        }

        return $deletedCount;
    }

    /**
     * Получает активные сессии пользователя
     */
    public function getUserActiveSessions(int $userId): array
    {
        return $this->sessionRepository->findActiveByUserId($userId);
    }

    /**
     * Создает нового пользователя
     */
    public function createUser(string $username, string $email, string $password, string $role = User::ROLE_USER): User
    {
        if ($this->userRepository->existsByUsername($username)) {
            throw new InvalidArgumentException("Username '{$username}' already exists");
        }

        if ($this->userRepository->existsByEmail($email)) {
            throw new InvalidArgumentException("Email '{$email}' already exists");
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $user = new User($username, $email, $passwordHash, $role);

        $this->userRepository->save($user);

        $this->logger->info('New user created', [
            'user_id' => $user->getId(),
            'username' => $username,
            'role' => $role
        ]);

        return $user;
    }

    /**
     * Изменяет пароль пользователя
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->userRepository->findById($userId);

        if (!$user || !$user->verifyPassword($currentPassword)) {
            return false;
        }

        $user->updatePassword($newPassword);
        $this->userRepository->save($user);

        // Завершаем все сессии пользователя
        $this->logoutAllSessions($userId);

        $this->logger->info('User password changed', [
            'user_id' => $userId,
            'username' => $user->getUsername()
        ]);

        return true;
    }
}
