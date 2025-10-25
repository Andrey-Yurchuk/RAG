<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Application\Service;

use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Application\Service\AuthService;
use RagSystem\Domain\Model\User;
use RagSystem\Domain\Model\UserSession;
use RagSystem\Domain\Repository\UserRepositoryInterface;
use RagSystem\Domain\Repository\UserSessionRepositoryInterface;
use Mockery;

/**
 * @covers \RagSystem\Application\Service\AuthService
 * @covers \RagSystem\Domain\Model\User
 * @covers \RagSystem\Domain\Model\UserSession
 */
class AuthServiceTest extends BaseTestCase
{
    private AuthService $authService;
    private UserRepositoryInterface $userRepository;
    private UserSessionRepositoryInterface $sessionRepository;
    private \Psr\Log\LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->sessionRepository = Mockery::mock(UserSessionRepositoryInterface::class);
        $this->logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        
        $this->authService = new AuthService(
            $this->userRepository,
            $this->sessionRepository,
            $this->logger
        );
    }

    /**
     * Тест успешной аутентификации
     */
    public function testAuthenticateSuccess(): void
    {
        $username = 'testuser';
        $password = 'password123';
        $ipAddress = '192.168.1.1';
        $userAgent = 'Mozilla/5.0';
        
        $user = new User($username, 'test@example.com', password_hash($password, PASSWORD_DEFAULT));
        $session = new UserSession(1, 'session_token', new \DateTimeImmutable('+1 day'));
        
        $this->userRepository->shouldReceive('findByUsername')
            ->with($username)
            ->andReturn($user)
            ->once();
        
        $this->userRepository->shouldReceive('save')
            ->with($user)
            ->once();
        
        $this->sessionRepository->shouldReceive('save')
            ->with(Mockery::type(UserSession::class))
            ->once();
        
        $this->logger->shouldReceive('warning')->never();
        $this->logger->shouldReceive('info')
            ->with('User authenticated successfully', Mockery::type('array'))
            ->once();
        
        $result = $this->authService->authenticate($username, $password, $ipAddress, $userAgent);
        
        $this->assertInstanceOf(UserSession::class, $result);
    }

    /**
     * Тест аутентификации с неверным паролем
     */
    public function testAuthenticateInvalidPassword(): void
    {
        $username = 'testuser';
        $password = 'wrongpassword';
        
        $user = new User($username, 'test@example.com', password_hash('correctpassword', PASSWORD_DEFAULT));
        
        $this->userRepository->shouldReceive('findByUsername')
            ->with($username)
            ->andReturn($user)
            ->once();
        
        $this->logger->shouldReceive('warning')
            ->with('Authentication failed: invalid password', ['username' => $username])
            ->once();
        
        $result = $this->authService->authenticate($username, $password);
        
        $this->assertNull($result);
    }

    /**
     * Тест аутентификации несуществующего пользователя
     */
    public function testAuthenticateUserNotFound(): void
    {
        $username = 'nonexistent';
        $password = 'password123';
        
        $this->userRepository->shouldReceive('findByUsername')
            ->with($username)
            ->andReturn(null)
            ->once();
        
        $this->logger->shouldReceive('warning')
            ->with('Authentication failed: user not found or inactive', ['username' => $username])
            ->once();
        
        $result = $this->authService->authenticate($username, $password);
        
        $this->assertNull($result);
    }

    /**
     * Тест валидации сессии
     */
    public function testValidateSession(): void
    {
        $sessionToken = 'valid_token';
        $user = new User('testuser', 'test@example.com', 'password_hash');
        $session = new UserSession(1, $sessionToken, new \DateTimeImmutable('+1 day'));
        
        $this->sessionRepository->shouldReceive('findByToken')
            ->with($sessionToken)
            ->andReturn($session)
            ->once();
        
        $this->userRepository->shouldReceive('findById')
            ->with(1)
            ->andReturn($user)
            ->once();
        
        $result = $this->authService->validateSession($sessionToken);
        
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user, $result);
    }

    /**
     * Тест валидации недействительной сессии
     */
    public function testValidateSessionInvalid(): void
    {
        $sessionToken = 'invalid_token';
        
        $this->sessionRepository->shouldReceive('findByToken')
            ->with($sessionToken)
            ->andReturn(null)
            ->once();
        
        $result = $this->authService->validateSession($sessionToken);
        
        $this->assertNull($result);
    }

    /**
     * Тест выхода из системы
     */
    public function testLogout(): void
    {
        $sessionToken = 'valid_token';
        $session = new UserSession(1, $sessionToken, new \DateTimeImmutable('+1 day'));
        
        $this->sessionRepository->shouldReceive('findByToken')
            ->with($sessionToken)
            ->andReturn($session)
            ->once();
        
        $this->sessionRepository->shouldReceive('deleteByToken')
            ->with($sessionToken)
            ->once();
        
        $this->logger->shouldReceive('info')
            ->with('User logged out', Mockery::type('array'))
            ->once();
        
        $result = $this->authService->logout($sessionToken);
        
        $this->assertTrue($result);
    }

    /**
     * Тест выхода из системы с несуществующей сессией
     */
    public function testLogoutNonExistentSession(): void
    {
        $sessionToken = 'nonexistent_token';
        
        $this->sessionRepository->shouldReceive('findByToken')
            ->with($sessionToken)
            ->andReturn(null)
            ->once();
        
        $result = $this->authService->logout($sessionToken);
        
        $this->assertFalse($result);
    }

    /**
     * Тест завершения всех сессий пользователя
     */
    public function testLogoutAllSessions(): void
    {
        $userId = 1;
        $sessions = [
            new UserSession($userId, 'token1', new \DateTimeImmutable('+1 day')),
            new UserSession($userId, 'token2', new \DateTimeImmutable('+1 day'))
        ];
        
        $this->sessionRepository->shouldReceive('findActiveByUserId')
            ->with($userId)
            ->andReturn($sessions)
            ->once();
        
        $this->sessionRepository->shouldReceive('deleteByUserId')
            ->with($userId)
            ->once();
        
        $this->logger->shouldReceive('info')
            ->with('All user sessions terminated', [
                'user_id' => $userId,
                'sessions_count' => 2
            ])
            ->once();
        
        $result = $this->authService->logoutAllSessions($userId);
        
        $this->assertEquals(2, $result);
    }

    /**
     * Тест продления сессии
     */
    public function testExtendSession(): void
    {
        $sessionToken = 'valid_token';
        $session = new UserSession(1, $sessionToken, new \DateTimeImmutable('+1 day'));
        
        $this->sessionRepository->shouldReceive('findByToken')
            ->with($sessionToken)
            ->andReturn($session)
            ->once();
        
        $this->sessionRepository->shouldReceive('save')
            ->with($session)
            ->once();
        
        $result = $this->authService->extendSession($sessionToken, 3600);
        
        $this->assertTrue($result);
    }

    /**
     * Тест продления несуществующей сессии
     */
    public function testExtendSessionNonExistent(): void
    {
        $sessionToken = 'nonexistent_token';
        
        $this->sessionRepository->shouldReceive('findByToken')
            ->with($sessionToken)
            ->andReturn(null)
            ->once();
        
        $result = $this->authService->extendSession($sessionToken);
        
        $this->assertFalse($result);
    }

    /**
     * Тест очистки истекших сессий
     */
    public function testCleanupExpiredSessions(): void
    {
        $this->sessionRepository->shouldReceive('deleteExpired')
            ->andReturn(5)
            ->once();
        
        $this->logger->shouldReceive('info')
            ->with('Expired sessions cleaned up', ['count' => 5])
            ->once();
        
        $result = $this->authService->cleanupExpiredSessions();
        
        $this->assertEquals(5, $result);
    }

    /**
     * Тест создания пользователя
     */
    public function testCreateUser(): void
    {
        $username = 'newuser';
        $email = 'new@example.com';
        $password = 'password123';
        $role = User::ROLE_USER;
        
        $this->userRepository->shouldReceive('existsByUsername')
            ->with($username)
            ->andReturn(false)
            ->once();
        
        $this->userRepository->shouldReceive('existsByEmail')
            ->with($email)
            ->andReturn(false)
            ->once();
        
        $this->userRepository->shouldReceive('save')
            ->with(Mockery::type(User::class))
            ->once();
        
        $this->logger->shouldReceive('info')
            ->with('New user created', Mockery::type('array'))
            ->once();
        
        $result = $this->authService->createUser($username, $email, $password, $role);
        
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($username, $result->getUsername());
        $this->assertEquals($email, $result->getEmail());
    }

    /**
     * Тест создания пользователя с существующим username
     */
    public function testCreateUserWithExistingUsername(): void
    {
        $username = 'existinguser';
        $email = 'new@example.com';
        $password = 'password123';
        
        $this->userRepository->shouldReceive('existsByUsername')
            ->with($username)
            ->andReturn(true)
            ->once();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Username '{$username}' already exists");
        
        $this->authService->createUser($username, $email, $password);
    }

    /**
     * Тест создания пользователя с существующим email
     */
    public function testCreateUserWithExistingEmail(): void
    {
        $username = 'newuser';
        $email = 'existing@example.com';
        $password = 'password123';
        
        $this->userRepository->shouldReceive('existsByUsername')
            ->with($username)
            ->andReturn(false)
            ->once();
        
        $this->userRepository->shouldReceive('existsByEmail')
            ->with($email)
            ->andReturn(true)
            ->once();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Email '{$email}' already exists");
        
        $this->authService->createUser($username, $email, $password);
    }

    /**
     * Тест изменения пароля
     */
    public function testChangePassword(): void
    {
        $userId = 1;
        $currentPassword = 'oldpassword';
        $newPassword = 'newpassword';
        
        $user = new User('testuser', 'test@example.com', password_hash($currentPassword, PASSWORD_DEFAULT));
        
        $this->userRepository->shouldReceive('findById')
            ->with($userId)
            ->andReturn($user)
            ->once();
        
        $this->userRepository->shouldReceive('save')
            ->with($user)
            ->once();
        
        $this->sessionRepository->shouldReceive('findActiveByUserId')
            ->with($userId)
            ->andReturn([])
            ->once();
        
        $this->sessionRepository->shouldReceive('deleteByUserId')
            ->with($userId)
            ->once();
        
        $this->logger->shouldReceive('info')
            ->with('All user sessions terminated', [
                'user_id' => $userId,
                'sessions_count' => 0
            ])
            ->once();
        
        $this->logger->shouldReceive('info')
            ->with('User password changed', Mockery::type('array'))
            ->once();
        
        $result = $this->authService->changePassword($userId, $currentPassword, $newPassword);
        
        $this->assertTrue($result);
    }

    /**
     * Тест изменения пароля с неверным текущим паролем
     */
    public function testChangePasswordInvalidCurrent(): void
    {
        $userId = 1;
        $currentPassword = 'wrongpassword';
        $newPassword = 'newpassword';
        
        $user = new User('testuser', 'test@example.com', password_hash('correctpassword', PASSWORD_DEFAULT));
        
        $this->userRepository->shouldReceive('findById')
            ->with($userId)
            ->andReturn($user)
            ->once();
        
        $result = $this->authService->changePassword($userId, $currentPassword, $newPassword);
        
        $this->assertFalse($result);
    }

    /**
     * Тест с русскими символами
     */
    public function testAuthenticateWithRussianText(): void
    {
        $username = 'пользователь';
        $password = 'пароль123';
        
        $user = new User($username, 'test@example.com', password_hash($password, PASSWORD_DEFAULT));
        
        $this->userRepository->shouldReceive('findByUsername')
            ->with($username)
            ->andReturn($user)
            ->once();
        
        $this->userRepository->shouldReceive('save')
            ->with($user)
            ->once();
        
        $this->sessionRepository->shouldReceive('save')
            ->with(Mockery::type(UserSession::class))
            ->once();
        
        $this->logger->shouldReceive('info')->andReturnSelf();
        
        $result = $this->authService->authenticate($username, $password);
        
        $this->assertInstanceOf(UserSession::class, $result);
    }

    /**
     * Тест получения активных сессий пользователя
     */
    public function testGetUserActiveSessions(): void
    {
        $userId = 1;
        $sessions = [
            new UserSession($userId, 'token1', new \DateTimeImmutable('+1 day')),
            new UserSession($userId, 'token2', new \DateTimeImmutable('+1 day')),
            new UserSession($userId, 'token3', new \DateTimeImmutable('+1 day'))
        ];
        
        $this->sessionRepository->shouldReceive('findActiveByUserId')
            ->with($userId)
            ->andReturn($sessions)
            ->once();
        
        $result = $this->authService->getUserActiveSessions($userId);
        
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertInstanceOf(UserSession::class, $result[0]);
        $this->assertInstanceOf(UserSession::class, $result[1]);
        $this->assertInstanceOf(UserSession::class, $result[2]);
    }

    /**
     * Тест получения активных сессий пользователя (пустой результат)
     */
    public function testGetUserActiveSessionsEmpty(): void
    {
        $userId = 1;
        
        $this->sessionRepository->shouldReceive('findActiveByUserId')
            ->with($userId)
            ->andReturn([])
            ->once();
        
        $result = $this->authService->getUserActiveSessions($userId);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Тест получения активных сессий пользователя с русскими символами
     */
    public function testGetUserActiveSessionsWithRussianText(): void
    {
        $userId = 1;
        $sessions = [
            new UserSession($userId, 'токен1', new \DateTimeImmutable('+1 day')),
            new UserSession($userId, 'токен2', new \DateTimeImmutable('+1 day'))
        ];
        
        $this->sessionRepository->shouldReceive('findActiveByUserId')
            ->with($userId)
            ->andReturn($sessions)
            ->once();
        
        $result = $this->authService->getUserActiveSessions($userId);
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(UserSession::class, $result[0]);
        $this->assertInstanceOf(UserSession::class, $result[1]);
    }

    /**
     * Тест аутентификации неактивного пользователя
     */
    public function testAuthenticateInactiveUser(): void
    {
        $username = 'inactiveuser';
        $password = 'password123';
        
        $user = new User($username, 'test@example.com', password_hash($password, PASSWORD_DEFAULT));
        $user->deactivate(); // Деактивируем пользователя
        
        $this->userRepository->shouldReceive('findByUsername')
            ->with($username)
            ->andReturn($user)
            ->once();
        
        $this->logger->shouldReceive('warning')
            ->with('Authentication failed: user not found or inactive', ['username' => $username])
            ->once();
        
        $result = $this->authService->authenticate($username, $password);
        
        $this->assertNull($result);
    }

    /**
     * Тест валидации истекшей сессии
     */
    public function testValidateSessionExpired(): void
    {
        $sessionToken = 'expired_token';
        $session = new UserSession(1, $sessionToken, new \DateTimeImmutable('-1 day')); // Истекшая сессия
        
        $this->sessionRepository->shouldReceive('findByToken')
            ->with($sessionToken)
            ->andReturn($session)
            ->once();
        
        $result = $this->authService->validateSession($sessionToken);
        
        $this->assertNull($result);
    }

    /**
     * Тест валидации сессии с несуществующим пользователем
     */
    public function testValidateSessionUserNotFound(): void
    {
        $sessionToken = 'valid_token';
        $session = new UserSession(999, $sessionToken, new \DateTimeImmutable('+1 day')); // Несуществующий пользователь
        
        $this->sessionRepository->shouldReceive('findByToken')
            ->with($sessionToken)
            ->andReturn($session)
            ->once();
        
        $this->userRepository->shouldReceive('findById')
            ->with(999)
            ->andReturn(null)
            ->once();
        
        $this->sessionRepository->shouldReceive('deleteByToken')
            ->with($sessionToken)
            ->once();
        
        $result = $this->authService->validateSession($sessionToken);
        
        $this->assertNull($result);
    }

    /**
     * Тест изменения пароля с несуществующим пользователем
     */
    public function testChangePasswordUserNotFound(): void
    {
        $userId = 999;
        $currentPassword = 'oldpassword';
        $newPassword = 'newpassword';
        
        $this->userRepository->shouldReceive('findById')
            ->with($userId)
            ->andReturn(null)
            ->once();
        
        $result = $this->authService->changePassword($userId, $currentPassword, $newPassword);
        
        $this->assertFalse($result);
    }

    /**
     * Тест продления сессии с истекшей сессией
     */
    public function testExtendSessionExpired(): void
    {
        $sessionToken = 'expired_token';
        $session = new UserSession(1, $sessionToken, new \DateTimeImmutable('-1 day')); // Истекшая сессия
        
        $this->sessionRepository->shouldReceive('findByToken')
            ->with($sessionToken)
            ->andReturn($session)
            ->once();
        
        $result = $this->authService->extendSession($sessionToken);
        
        $this->assertFalse($result);
    }

    /**
     * Тест продления сессии с дефолтным временем
     */
    public function testExtendSessionWithDefaultTime(): void
    {
        $sessionToken = 'valid_token';
        $session = new UserSession(1, $sessionToken, new \DateTimeImmutable('+1 day'));
        
        $this->sessionRepository->shouldReceive('findByToken')
            ->with($sessionToken)
            ->andReturn($session)
            ->once();
        
        $this->sessionRepository->shouldReceive('save')
            ->with($session)
            ->once();
        
        $result = $this->authService->extendSession($sessionToken); // Без указания времени
        
        $this->assertTrue($result);
    }

    /**
     * Тест очистки истекших сессий (пустой результат)
     */
    public function testCleanupExpiredSessionsEmpty(): void
    {
        $this->sessionRepository->shouldReceive('deleteExpired')
            ->andReturn(0)
            ->once();
        
        $result = $this->authService->cleanupExpiredSessions();
        
        $this->assertEquals(0, $result);
    }

    /**
     * Тест создания пользователя с дефолтной ролью
     */
    public function testCreateUserWithDefaultRole(): void
    {
        $username = 'newuser';
        $email = 'new@example.com';
        $password = 'password123';
        
        $this->userRepository->shouldReceive('existsByUsername')
            ->with($username)
            ->andReturn(false)
            ->once();
        
        $this->userRepository->shouldReceive('existsByEmail')
            ->with($email)
            ->andReturn(false)
            ->once();
        
        $this->userRepository->shouldReceive('save')
            ->with(Mockery::type(User::class))
            ->once();
        
        $this->logger->shouldReceive('info')
            ->with('New user created', Mockery::type('array'))
            ->once();
        
        $result = $this->authService->createUser($username, $email, $password); // Без указания роли
        
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($username, $result->getUsername());
        $this->assertEquals($email, $result->getEmail());
        $this->assertEquals(User::ROLE_USER, $result->getRole()); // Дефолтная роль
    }

    /**
     * Тест создания пользователя с админской ролью
     */
    public function testCreateUserWithAdminRole(): void
    {
        $username = 'adminuser';
        $email = 'admin@example.com';
        $password = 'password123';
        $role = User::ROLE_ADMIN;
        
        $this->userRepository->shouldReceive('existsByUsername')
            ->with($username)
            ->andReturn(false)
            ->once();
        
        $this->userRepository->shouldReceive('existsByEmail')
            ->with($email)
            ->andReturn(false)
            ->once();
        
        $this->userRepository->shouldReceive('save')
            ->with(Mockery::type(User::class))
            ->once();
        
        $this->logger->shouldReceive('info')
            ->with('New user created', Mockery::type('array'))
            ->once();
        
        $result = $this->authService->createUser($username, $email, $password, $role);
        
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($username, $result->getUsername());
        $this->assertEquals($email, $result->getEmail());
        $this->assertEquals($role, $result->getRole());
    }

    /**
     * Тест создания пользователя с русскими символами
     */
    public function testCreateUserWithRussianText(): void
    {
        $username = 'пользователь';
        $email = 'пользователь@example.com';
        $password = 'пароль123';
        
        $this->userRepository->shouldReceive('existsByUsername')
            ->with($username)
            ->andReturn(false)
            ->once();
        
        $this->userRepository->shouldReceive('existsByEmail')
            ->with($email)
            ->andReturn(false)
            ->once();
        
        $this->userRepository->shouldReceive('save')
            ->with(Mockery::type(User::class))
            ->once();
        
        $this->logger->shouldReceive('info')
            ->with('New user created', Mockery::type('array'))
            ->once();
        
        $result = $this->authService->createUser($username, $email, $password);
        
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($username, $result->getUsername());
        $this->assertEquals($email, $result->getEmail());
    }
}
