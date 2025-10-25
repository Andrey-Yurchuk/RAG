<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use RagSystem\Domain\Model\User;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * @covers \RagSystem\Domain\Model\User
 */
class UserTest extends TestCase
{
    /**
     * Тест конструктора User
     */
    public function testUserConstructor(): void
    {
        $user = new User('testuser', 'test@example.com', 'password_hash');
        
        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals(User::ROLE_USER, $user->getRole());
        $this->assertTrue($user->isActive());
        $this->assertNull($user->getLastLoginAt());
        $this->assertEquals(0, $user->getId());
    }

    /**
     * Тест конструктора User с ролью
     */
    public function testUserConstructorWithRole(): void
    {
        $user = new User('admin', 'admin@example.com', 'password_hash', User::ROLE_ADMIN);
        
        $this->assertEquals(User::ROLE_ADMIN, $user->getRole());
        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isUser());
    }

    /**
     * Тест конструктора User с неактивным статусом
     */
    public function testUserConstructorWithInactiveStatus(): void
    {
        $user = new User('testuser', 'test@example.com', 'password_hash', User::ROLE_USER, false);
        
        $this->assertFalse($user->isActive());
        $this->assertTrue($user->isUser());
        $this->assertFalse($user->isAdmin());
    }

    /**
     * Тест конструктора User с последним входом
     */
    public function testUserConstructorWithLastLogin(): void
    {
        $lastLogin = new DateTimeImmutable('2023-01-01 12:00:00');
        $user = new User('testuser', 'test@example.com', 'password_hash', User::ROLE_USER, true, $lastLogin);
        
        $this->assertEquals($lastLogin, $user->getLastLoginAt());
    }

    /**
     * Тест создания User из массива
     */
    public function testUserFromArray(): void
    {
        $data = [
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password_hash' => 'password_hash',
            'role' => User::ROLE_ADMIN,
            'is_active' => false,
            'created_at' => '2023-01-01 10:00:00',
            'updated_at' => '2023-01-01 11:00:00',
            'last_login_at' => '2023-01-01 12:00:00'
        ];
        
        $user = User::fromArray($data);
        
        $this->assertEquals(1, $user->getId());
        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals(User::ROLE_ADMIN, $user->getRole());
        $this->assertFalse($user->isActive());
        $this->assertEquals('2023-01-01 10:00:00', $user->getCreatedAt()->format('Y-m-d H:i:s'));
        $this->assertEquals('2023-01-01 11:00:00', $user->getUpdatedAt()->format('Y-m-d H:i:s'));
        $this->assertEquals('2023-01-01 12:00:00', $user->getLastLoginAt()->format('Y-m-d H:i:s'));
    }

    /**
     * Тест создания User из массива с значениями по умолчанию
     */
    public function testUserFromArrayWithDefaults(): void
    {
        $data = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password_hash' => 'password_hash'
        ];
        
        $user = User::fromArray($data);
        
        $this->assertEquals(0, $user->getId());
        $this->assertEquals(User::ROLE_USER, $user->getRole());
        $this->assertTrue($user->isActive());
        $this->assertNull($user->getLastLoginAt());
    }

    /**
     * Тест проверки пароля User
     */
    public function testUserVerifyPassword(): void
    {
        $password = 'testpassword';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $user = new User('testuser', 'test@example.com', $hash);
        
        $this->assertTrue($user->verifyPassword($password));
        $this->assertFalse($user->verifyPassword('wrongpassword'));
    }

    /**
     * Тест обновления пароля User
     */
    public function testUserUpdatePassword(): void
    {
        $user = new User('testuser', 'test@example.com', 'old_hash');
        $oldUpdatedAt = $user->getUpdatedAt();

        usleep(1000);
        
        $user->updatePassword('newpassword');
        
        $this->assertTrue($user->verifyPassword('newpassword'));
        $this->assertFalse($user->verifyPassword('oldpassword'));
        $this->assertGreaterThan($oldUpdatedAt, $user->getUpdatedAt());
    }

    /**
     * Тест обновления последнего входа User
     */
    public function testUserUpdateLastLogin(): void
    {
        $user = new User('testuser', 'test@example.com', 'password_hash');
        $oldUpdatedAt = $user->getUpdatedAt();
        
        $this->assertNull($user->getLastLoginAt());

        usleep(1000);
        
        $user->updateLastLogin();
        
        $this->assertNotNull($user->getLastLoginAt());
        $this->assertGreaterThan($oldUpdatedAt, $user->getUpdatedAt());
    }

    /**
     * Тест активации User
     */
    public function testUserActivate(): void
    {
        $user = new User('testuser', 'test@example.com', 'password_hash', User::ROLE_USER, false);
        $oldUpdatedAt = $user->getUpdatedAt();
        
        $this->assertFalse($user->isActive());

        usleep(1000);
        
        $user->activate();
        
        $this->assertTrue($user->isActive());
        $this->assertGreaterThan($oldUpdatedAt, $user->getUpdatedAt());
    }

    /**
     * Тест деактивации User
     */
    public function testUserDeactivate(): void
    {
        $user = new User('testuser', 'test@example.com', 'password_hash', User::ROLE_USER, true);
        $oldUpdatedAt = $user->getUpdatedAt();
        
        $this->assertTrue($user->isActive());

        usleep(1000);
        
        $user->deactivate();
        
        $this->assertFalse($user->isActive());
        $this->assertGreaterThan($oldUpdatedAt, $user->getUpdatedAt());
    }

    /**
     * Тест изменения роли User
     */
    public function testUserChangeRole(): void
    {
        $user = new User('testuser', 'test@example.com', 'password_hash', User::ROLE_USER);
        $oldUpdatedAt = $user->getUpdatedAt();
        
        $this->assertEquals(User::ROLE_USER, $user->getRole());
        $this->assertTrue($user->isUser());
        $this->assertFalse($user->isAdmin());

        usleep(1000);
        
        $user->changeRole(User::ROLE_ADMIN);
        
        $this->assertEquals(User::ROLE_ADMIN, $user->getRole());
        $this->assertFalse($user->isUser());
        $this->assertTrue($user->isAdmin());
        $this->assertGreaterThan($oldUpdatedAt, $user->getUpdatedAt());
    }

    /**
     * Тест изменения роли User с неверной ролью
     */
    public function testUserChangeRoleInvalid(): void
    {
        $user = new User('testuser', 'test@example.com', 'password_hash', User::ROLE_USER);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid role: invalid_role');
        
        $user->changeRole('invalid_role');
    }

    /**
     * Тест обновления email User
     */
    public function testUserUpdateEmail(): void
    {
        $user = new User('testuser', 'old@example.com', 'password_hash');
        $oldUpdatedAt = $user->getUpdatedAt();
        
        $this->assertEquals('old@example.com', $user->getEmail());

        usleep(1000);
        
        $user->updateEmail('new@example.com');
        
        $this->assertEquals('new@example.com', $user->getEmail());
        $this->assertGreaterThan($oldUpdatedAt, $user->getUpdatedAt());
    }

    /**
     * Тест обновления имени пользователя User
     */
    public function testUserUpdateUsername(): void
    {
        $user = new User('olduser', 'test@example.com', 'password_hash');
        $oldUpdatedAt = $user->getUpdatedAt();
        
        $this->assertEquals('olduser', $user->getUsername());

        usleep(1000);
        
        $user->updateUsername('newuser');
        
        $this->assertEquals('newuser', $user->getUsername());
        $this->assertGreaterThan($oldUpdatedAt, $user->getUpdatedAt());
    }

    /**
     * Тест преобразования User в массив
     */
    public function testUserToArray(): void
    {
        $user = new User('testuser', 'test@example.com', 'password_hash', User::ROLE_ADMIN, true);
        
        $array = $user->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals(0, $array['id']);
        $this->assertEquals('testuser', $array['username']);
        $this->assertEquals('test@example.com', $array['email']);
        $this->assertEquals('password_hash', $array['password_hash']);
        $this->assertEquals(User::ROLE_ADMIN, $array['role']);
        $this->assertTrue($array['is_active']);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
        $this->assertNull($array['last_login_at']);
    }

    /**
     * Тест констант User
     */
    public function testUserConstants(): void
    {
        $this->assertEquals('admin', User::ROLE_ADMIN);
        $this->assertEquals('user', User::ROLE_USER);
    }
}