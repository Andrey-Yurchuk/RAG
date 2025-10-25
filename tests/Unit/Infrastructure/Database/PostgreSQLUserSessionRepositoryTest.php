<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Infrastructure\Database;

use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Infrastructure\Database\PostgreSQLUserSessionRepository;
use RagSystem\Domain\Model\UserSession;
use DateTimeImmutable;
use Mockery;
use ReflectionClass;

/**
 * @covers \RagSystem\Infrastructure\Database\PostgreSQLUserSessionRepository
 * @covers \RagSystem\Domain\Model\UserSession
 */
class PostgreSQLUserSessionRepositoryTest extends BaseTestCase
{
    private PostgreSQLUserSessionRepository $repository;
    private \Doctrine\DBAL\Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->connection = Mockery::mock(\Doctrine\DBAL\Connection::class);
        
        $this->repository = new PostgreSQLUserSessionRepository($this->connection);
    }

    /**
     * Тест сохранения новой сессии
     */
    public function testSaveNewSession(): void
    {
        $expiresAt = new DateTimeImmutable('+1 hour');
        $session = new UserSession(123, 'session_token_123', $expiresAt, '192.168.1.1', 'Mozilla/5.0');
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->once();
        
        $this->connection->shouldReceive('lastInsertId')
            ->andReturn('456')
            ->once();
        
        $this->repository->save($session);
        
        $this->assertTrue(true);
    }

    /**
     * Тест сохранения существующей сессии
     */
    public function testSaveExistingSession(): void
    {
        $expiresAt = new DateTimeImmutable('+1 hour');
        $session = new UserSession(123, 'session_token_123', $expiresAt, '192.168.1.1', 'Mozilla/5.0');

        $reflection = new ReflectionClass($session);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($session, 456);
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->once();
        
        $this->repository->save($session);
        
        $this->assertTrue(true);
    }

    /**
     * Тест поиска сессии по токену
     */
    public function testFindSessionByToken(): void
    {
        $token = 'session_token_123';
        $expiresAt = new DateTimeImmutable('+1 hour');
        $expectedData = [
            'id' => 456,
            'user_id' => 123,
            'session_token' => $token,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'created_at' => '2023-01-01 12:00:00'
        ];
        
        $this->connection->shouldReceive('fetchAssociative')
            ->with(Mockery::type('string'), ['token' => $token])
            ->andReturn($expectedData)
            ->once();
        
        $result = $this->repository->findByToken($token);
        
        $this->assertInstanceOf(UserSession::class, $result);
        $this->assertEquals($token, $result->getSessionToken());
        $this->assertEquals(123, $result->getUserId());
    }

    /**
     * Тест поиска сессии по токену (не найдена)
     */
    public function testFindSessionByTokenNotFound(): void
    {
        $token = 'nonexistent_token';
        
        $this->connection->shouldReceive('fetchAssociative')
            ->with(Mockery::type('string'), ['token' => $token])
            ->andReturn(false)
            ->once();
        
        $result = $this->repository->findByToken($token);
        
        $this->assertNull($result);
    }

    /**
     * Тест поиска активных сессий пользователя
     */
    public function testFindActiveSessionsByUserId(): void
    {
        $userId = 123;
        $expiresAt = new DateTimeImmutable('+1 hour');
        $expectedData = [
            [
                'id' => 456,
                'user_id' => $userId,
                'session_token' => 'token1',
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0',
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'created_at' => '2023-01-01 12:00:00'
            ],
            [
                'id' => 457,
                'user_id' => $userId,
                'session_token' => 'token2',
                'ip_address' => '192.168.1.2',
                'user_agent' => 'Chrome/91.0',
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'created_at' => '2023-01-02 12:00:00'
            ]
        ];
        
        $this->connection->shouldReceive('fetchAllAssociative')
            ->with(Mockery::type('string'), ['user_id' => $userId])
            ->andReturn($expectedData)
            ->once();
        
        $result = $this->repository->findActiveByUserId($userId);
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(UserSession::class, $result[0]);
        $this->assertInstanceOf(UserSession::class, $result[1]);
    }

    /**
     * Тест поиска активных сессий пользователя (пустой результат)
     */
    public function testFindActiveSessionsByUserIdEmpty(): void
    {
        $userId = 999;
        
        $this->connection->shouldReceive('fetchAllAssociative')
            ->with(Mockery::type('string'), ['user_id' => $userId])
            ->andReturn([])
            ->once();
        
        $result = $this->repository->findActiveByUserId($userId);
        
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    /**
     * Тест поиска всех сессий пользователя
     */
    public function testFindSessionsByUserId(): void
    {
        $userId = 123;
        $expiresAt = new DateTimeImmutable('+1 hour');
        $expectedData = [
            [
                'id' => 456,
                'user_id' => $userId,
                'session_token' => 'token1',
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0',
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'created_at' => '2023-01-01 12:00:00'
            ]
        ];
        
        $this->connection->shouldReceive('fetchAllAssociative')
            ->with(Mockery::type('string'), ['user_id' => $userId])
            ->andReturn($expectedData)
            ->once();
        
        $result = $this->repository->findByUserId($userId);
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(UserSession::class, $result[0]);
    }

    /**
     * Тест удаления сессии по токену
     */
    public function testDeleteSessionByToken(): void
    {
        $token = 'session_token_123';
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), ['token' => $token])
            ->once();
        
        $this->repository->deleteByToken($token);
        
        $this->assertTrue(true);
    }

    /**
     * Тест удаления всех сессий пользователя
     */
    public function testDeleteSessionsByUserId(): void
    {
        $userId = 123;
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), ['user_id' => $userId])
            ->once();
        
        $this->repository->deleteByUserId($userId);
        
        $this->assertTrue(true);
    }

    /**
     * Тест удаления истекших сессий
     */
    public function testDeleteExpiredSessions(): void
    {
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'))
            ->andReturn(5)
            ->once();
        
        $result = $this->repository->deleteExpired();
        
        $this->assertEquals(5, $result);
    }

    /**
     * Тест удаления сессии по ID
     */
    public function testDeleteSessionById(): void
    {
        $id = 456;
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), ['id' => $id])
            ->once();
        
        $this->repository->deleteById($id);
        
        $this->assertTrue(true);
    }

    /**
     * Тест подсчета активных сессий пользователя
     */
    public function testCountActiveSessionsByUserId(): void
    {
        $userId = 123;
        
        $this->connection->shouldReceive('fetchOne')
            ->with(Mockery::type('string'), ['user_id' => $userId])
            ->andReturn('3')
            ->once();
        
        $result = $this->repository->countActiveByUserId($userId);
        
        $this->assertEquals(3, $result);
    }

    /**
     * Тест подсчета всех активных сессий
     */
    public function testCountActiveSessions(): void
    {
        $this->connection->shouldReceive('fetchOne')
            ->with(Mockery::type('string'))
            ->andReturn('25')
            ->once();
        
        $result = $this->repository->countActive();
        
        $this->assertEquals(25, $result);
    }

    /**
     * Тест с русскими символами в user agent
     */
    public function testWithRussianUserAgent(): void
    {
        $expiresAt = new DateTimeImmutable('+1 hour');
        $session = new UserSession(123, 'session_token_123', $expiresAt, '192.168.1.1', 'Браузер с русскими символами');
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->once();
        
        $this->connection->shouldReceive('lastInsertId')
            ->andReturn('456')
            ->once();
        
        $this->repository->save($session);
        
        $this->assertTrue(true);
    }

    /**
     * Тест с null значениями
     */
    public function testWithNullValues(): void
    {
        $expiresAt = new DateTimeImmutable('+1 hour');
        $session = new UserSession(123, 'session_token_123', $expiresAt, null, null);
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->once();
        
        $this->connection->shouldReceive('lastInsertId')
            ->andReturn('456')
            ->once();
        
        $this->repository->save($session);
        
        $this->assertTrue(true);
    }

    /**
     * Тест с большим количеством сессий
     */
    public function testWithManySessions(): void
    {
        $userId = 123;
        $expectedData = array_fill(0, 100, [
            'id' => 456,
            'user_id' => $userId,
            'session_token' => 'token',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'expires_at' => '2023-12-31 23:59:59',
            'created_at' => '2023-01-01 12:00:00'
        ]);
        
        $this->connection->shouldReceive('fetchAllAssociative')
            ->with(Mockery::type('string'), ['user_id' => $userId])
            ->andReturn($expectedData)
            ->once();
        
        $result = $this->repository->findByUserId($userId);
        
        $this->assertIsArray($result);
        $this->assertCount(100, $result);
    }

    /**
     * Тест с различными IP адресами
     */
    public function testWithDifferentIpAddresses(): void
    {
        $expiresAt = new DateTimeImmutable('+1 hour');
        $session = new UserSession(123, 'session_token_123', $expiresAt, '10.0.0.1', 'Mozilla/5.0');
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->once();
        
        $this->connection->shouldReceive('lastInsertId')
            ->andReturn('456')
            ->once();
        
        $this->repository->save($session);
        
        $this->assertTrue(true);
    }

    /**
     * Тест с различными браузерами
     */
    public function testWithDifferentBrowsers(): void
    {
        $expiresAt = new DateTimeImmutable('+1 hour');
        $session = new UserSession(123, 'session_token_123', $expiresAt, '192.168.1.1', 'Chrome/91.0.4472.124 Safari/537.36');
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->once();
        
        $this->connection->shouldReceive('lastInsertId')
            ->andReturn('456')
            ->once();
        
        $this->repository->save($session);
        
        $this->assertTrue(true);
    }
}
