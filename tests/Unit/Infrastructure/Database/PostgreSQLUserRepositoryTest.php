<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Infrastructure\Database;

use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Infrastructure\Database\PostgreSQLUserRepository;
use RagSystem\Domain\Model\User;
use Mockery;
use ReflectionClass;

/**
 * @covers \RagSystem\Infrastructure\Database\PostgreSQLUserRepository
 * @covers \RagSystem\Domain\Model\User
 */
class PostgreSQLUserRepositoryTest extends BaseTestCase
{
    private PostgreSQLUserRepository $repository;
    private \Doctrine\DBAL\Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->connection = Mockery::mock(\Doctrine\DBAL\Connection::class);
        
        $this->repository = new PostgreSQLUserRepository($this->connection);
    }

    /**
     * Тест сохранения нового пользователя
     */
    public function testSaveNewUser(): void
    {
        $user = new User('testuser', 'test@example.com', 'password_hash', User::ROLE_USER);
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->once();
        
        $this->connection->shouldReceive('lastInsertId')
            ->andReturn('123')
            ->once();
        
        $this->repository->save($user);
        
        $this->assertTrue(true);
    }

    /**
     * Тест сохранения существующего пользователя
     */
    public function testSaveExistingUser(): void
    {
        $user = new User('testuser', 'test@example.com', 'password_hash', User::ROLE_USER);

        $reflection = new ReflectionClass($user);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($user, 123);
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->once();
        
        $this->repository->save($user);
        
        $this->assertTrue(true);
    }

    /**
     * Тест поиска пользователя по ID
     */
    public function testFindUserById(): void
    {
        $id = 123;
        $expectedData = [
            'id' => $id,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password_hash' => 'password_hash',
            'role' => User::ROLE_USER,
            'is_active' => true,
            'created_at' => '2023-01-01 12:00:00',
            'updated_at' => '2023-01-01 12:00:00',
            'last_login_at' => null
        ];
        
        $this->connection->shouldReceive('fetchAssociative')
            ->with(Mockery::type('string'), ['id' => $id])
            ->andReturn($expectedData)
            ->once();
        
        $result = $this->repository->findById($id);
        
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('testuser', $result->getUsername());
        $this->assertEquals('test@example.com', $result->getEmail());
    }

    /**
     * Тест поиска пользователя по ID (не найден)
     */
    public function testFindUserByIdNotFound(): void
    {
        $id = 999;
        
        $this->connection->shouldReceive('fetchAssociative')
            ->with(Mockery::type('string'), ['id' => $id])
            ->andReturn(false)
            ->once();
        
        $result = $this->repository->findById($id);
        
        $this->assertNull($result);
    }

    /**
     * Тест поиска пользователя по имени
     */
    public function testFindUserByUsername(): void
    {
        $username = 'testuser';
        $expectedData = [
            'id' => 123,
            'username' => $username,
            'email' => 'test@example.com',
            'password_hash' => 'password_hash',
            'role' => User::ROLE_USER,
            'is_active' => true,
            'created_at' => '2023-01-01 12:00:00',
            'updated_at' => '2023-01-01 12:00:00',
            'last_login_at' => null
        ];
        
        $this->connection->shouldReceive('fetchAssociative')
            ->with(Mockery::type('string'), ['username' => $username])
            ->andReturn($expectedData)
            ->once();
        
        $result = $this->repository->findByUsername($username);
        
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($username, $result->getUsername());
    }

    /**
     * Тест поиска пользователя по имени (не найден)
     */
    public function testFindUserByUsernameNotFound(): void
    {
        $username = 'nonexistent';
        
        $this->connection->shouldReceive('fetchAssociative')
            ->with(Mockery::type('string'), ['username' => $username])
            ->andReturn(false)
            ->once();
        
        $result = $this->repository->findByUsername($username);
        
        $this->assertNull($result);
    }

    /**
     * Тест поиска пользователя по email
     */
    public function testFindUserByEmail(): void
    {
        $email = 'test@example.com';
        $expectedData = [
            'id' => 123,
            'username' => 'testuser',
            'email' => $email,
            'password_hash' => 'password_hash',
            'role' => User::ROLE_USER,
            'is_active' => true,
            'created_at' => '2023-01-01 12:00:00',
            'updated_at' => '2023-01-01 12:00:00',
            'last_login_at' => null
        ];
        
        $this->connection->shouldReceive('fetchAssociative')
            ->with(Mockery::type('string'), ['email' => $email])
            ->andReturn($expectedData)
            ->once();
        
        $result = $this->repository->findByEmail($email);
        
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($email, $result->getEmail());
    }

    /**
     * Тест поиска пользователя по email (не найден)
     */
    public function testFindUserByEmailNotFound(): void
    {
        $email = 'nonexistent@example.com';
        
        $this->connection->shouldReceive('fetchAssociative')
            ->with(Mockery::type('string'), ['email' => $email])
            ->andReturn(false)
            ->once();
        
        $result = $this->repository->findByEmail($email);
        
        $this->assertNull($result);
    }

    /**
     * Тест поиска всех пользователей
     */
    public function testFindAllUsers(): void
    {
        $expectedData = [
            [
                'id' => 123,
                'username' => 'user1',
                'email' => 'user1@example.com',
                'password_hash' => 'hash1',
                'role' => User::ROLE_USER,
                'is_active' => true,
                'created_at' => '2023-01-01 12:00:00',
                'updated_at' => '2023-01-01 12:00:00',
                'last_login_at' => null
            ],
            [
                'id' => 124,
                'username' => 'user2',
                'email' => 'user2@example.com',
                'password_hash' => 'hash2',
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
                'created_at' => '2023-01-02 12:00:00',
                'updated_at' => '2023-01-02 12:00:00',
                'last_login_at' => null
            ]
        ];
        
        $this->connection->shouldReceive('fetchAllAssociative')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->andReturn($expectedData)
            ->once();
        
        $result = $this->repository->findAll();
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertInstanceOf(User::class, $result[1]);
    }

    /**
     * Тест поиска всех пользователей с параметрами
     */
    public function testFindAllUsersWithParams(): void
    {
        $expectedData = [
            [
                'id' => 123,
                'username' => 'user1',
                'email' => 'user1@example.com',
                'password_hash' => 'hash1',
                'role' => User::ROLE_USER,
                'is_active' => true,
                'created_at' => '2023-01-01 12:00:00',
                'updated_at' => '2023-01-01 12:00:00',
                'last_login_at' => null
            ]
        ];
        
        $this->connection->shouldReceive('fetchAllAssociative')
            ->with(Mockery::type('string'), ['limit' => 5, 'offset' => 10])
            ->andReturn($expectedData)
            ->once();
        
        $result = $this->repository->findAll(5, 10);
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(User::class, $result[0]);
    }

    /**
     * Тест поиска пользователей по роли
     */
    public function testFindUsersByRole(): void
    {
        $role = User::ROLE_ADMIN;
        $expectedData = [
            [
                'id' => 123,
                'username' => 'admin1',
                'email' => 'admin1@example.com',
                'password_hash' => 'hash1',
                'role' => $role,
                'is_active' => true,
                'created_at' => '2023-01-01 12:00:00',
                'updated_at' => '2023-01-01 12:00:00',
                'last_login_at' => null
            ]
        ];
        
        $this->connection->shouldReceive('fetchAllAssociative')
            ->with(Mockery::type('string'), ['role' => $role, 'limit' => 10, 'offset' => 0])
            ->andReturn($expectedData)
            ->once();
        
        $result = $this->repository->findByRole($role);
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(User::class, $result[0]);
    }

    /**
     * Тест поиска пользователей по роли с параметрами
     */
    public function testFindUsersByRoleWithParams(): void
    {
        $role = User::ROLE_USER;
        $expectedData = [];
        
        $this->connection->shouldReceive('fetchAllAssociative')
            ->with(Mockery::type('string'), ['role' => $role, 'limit' => 5, 'offset' => 10])
            ->andReturn($expectedData)
            ->once();
        
        $result = $this->repository->findByRole($role, 5, 10);
        
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    /**
     * Тест удаления пользователя по ID
     */
    public function testDeleteUserById(): void
    {
        $id = 123;
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), ['id' => $id])
            ->once();
        
        $this->repository->deleteById($id);
        
        $this->assertTrue(true);
    }

    /**
     * Тест проверки существования пользователя по имени
     */
    public function testExistsByUsername(): void
    {
        $username = 'testuser';
        
        $this->connection->shouldReceive('fetchOne')
            ->with(Mockery::type('string'), ['username' => $username])
            ->andReturn('1')
            ->once();
        
        $result = $this->repository->existsByUsername($username);
        
        $this->assertTrue($result);
    }

    /**
     * Тест проверки существования пользователя по имени (не существует)
     */
    public function testExistsByUsernameNotFound(): void
    {
        $username = 'nonexistent';
        
        $this->connection->shouldReceive('fetchOne')
            ->with(Mockery::type('string'), ['username' => $username])
            ->andReturn(false)
            ->once();
        
        $result = $this->repository->existsByUsername($username);
        
        $this->assertFalse($result);
    }

    /**
     * Тест проверки существования пользователя по email
     */
    public function testExistsByEmail(): void
    {
        $email = 'test@example.com';
        
        $this->connection->shouldReceive('fetchOne')
            ->with(Mockery::type('string'), ['email' => $email])
            ->andReturn('1')
            ->once();
        
        $result = $this->repository->existsByEmail($email);
        
        $this->assertTrue($result);
    }

    /**
     * Тест проверки существования пользователя по email (не существует)
     */
    public function testExistsByEmailNotFound(): void
    {
        $email = 'nonexistent@example.com';
        
        $this->connection->shouldReceive('fetchOne')
            ->with(Mockery::type('string'), ['email' => $email])
            ->andReturn(false)
            ->once();
        
        $result = $this->repository->existsByEmail($email);
        
        $this->assertFalse($result);
    }

    /**
     * Тест подсчета всех пользователей
     */
    public function testCountUsers(): void
    {
        $this->connection->shouldReceive('fetchOne')
            ->with(Mockery::type('string'))
            ->andReturn('25')
            ->once();
        
        $result = $this->repository->count();
        
        $this->assertEquals(25, $result);
    }

    /**
     * Тест подсчета пользователей по роли
     */
    public function testCountUsersByRole(): void
    {
        $role = User::ROLE_ADMIN;
        
        $this->connection->shouldReceive('fetchOne')
            ->with(Mockery::type('string'), ['role' => $role])
            ->andReturn('5')
            ->once();
        
        $result = $this->repository->countByRole($role);
        
        $this->assertEquals(5, $result);
    }

    /**
     * Тест с русскими символами
     */
    public function testWithRussianText(): void
    {
        $user = new User('пользователь', 'user@example.com', 'password_hash', User::ROLE_USER);
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->once();
        
        $this->connection->shouldReceive('lastInsertId')
            ->andReturn('123')
            ->once();
        
        $this->repository->save($user);
        
        $this->assertTrue(true);
    }

    /**
     * Тест с неактивным пользователем
     */
    public function testWithInactiveUser(): void
    {
        $user = new User('testuser', 'test@example.com', 'password_hash', User::ROLE_USER, false);
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->once();
        
        $this->connection->shouldReceive('lastInsertId')
            ->andReturn('123')
            ->once();
        
        $this->repository->save($user);
        
        $this->assertTrue(true);
    }

    /**
     * Тест с администратором
     */
    public function testWithAdminUser(): void
    {
        $user = new User('admin', 'admin@example.com', 'password_hash', User::ROLE_ADMIN);
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->once();
        
        $this->connection->shouldReceive('lastInsertId')
            ->andReturn('123')
            ->once();
        
        $this->repository->save($user);
        
        $this->assertTrue(true);
    }
}
