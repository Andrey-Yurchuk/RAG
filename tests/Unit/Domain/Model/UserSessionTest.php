<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use RagSystem\Domain\Model\UserSession;
use DateTimeImmutable;

/**
 * @covers \RagSystem\Domain\Model\UserSession
 */
class UserSessionTest extends TestCase
{
    /**
     * Тест конструктора UserSession
     */
    public function testUserSessionConstructor(): void
    {
        $expiresAt = new DateTimeImmutable('+1 hour');
        $session = new UserSession(1, 'session_token', $expiresAt, '192.168.1.1', 'Mozilla/5.0');
        
        $this->assertEquals(1, $session->getUserId());
        $this->assertEquals('session_token', $session->getSessionToken());
        $this->assertEquals($expiresAt, $session->getExpiresAt());
        $this->assertEquals('192.168.1.1', $session->getIpAddress());
        $this->assertEquals('Mozilla/5.0', $session->getUserAgent());
        $this->assertEquals(0, $session->getId());
    }

    /**
     * Тест конструктора UserSession с значениями по умолчанию
     */
    public function testUserSessionConstructorWithDefaults(): void
    {
        $expiresAt = new DateTimeImmutable('+1 hour');
        $session = new UserSession(1, 'session_token', $expiresAt);
        
        $this->assertEquals(1, $session->getUserId());
        $this->assertEquals('session_token', $session->getSessionToken());
        $this->assertEquals($expiresAt, $session->getExpiresAt());
        $this->assertNull($session->getIpAddress());
        $this->assertNull($session->getUserAgent());
    }

    /**
     * Тест создания UserSession из массива
     */
    public function testUserSessionFromArray(): void
    {
        $data = [
            'id' => 1,
            'user_id' => 1,
            'session_token' => 'session_token',
            'expires_at' => '2023-01-01 12:00:00',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'created_at' => '2023-01-01 10:00:00'
        ];
        
        $session = UserSession::fromArray($data);
        
        $this->assertEquals(1, $session->getId());
        $this->assertEquals(1, $session->getUserId());
        $this->assertEquals('session_token', $session->getSessionToken());
        $this->assertEquals('2023-01-01 12:00:00', $session->getExpiresAt()->format('Y-m-d H:i:s'));
        $this->assertEquals('192.168.1.1', $session->getIpAddress());
        $this->assertEquals('Mozilla/5.0', $session->getUserAgent());
        $this->assertEquals('2023-01-01 10:00:00', $session->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    /**
     * Тест создания UserSession из массива с значениями по умолчанию
     */
    public function testUserSessionFromArrayWithDefaults(): void
    {
        $data = [
            'user_id' => 1,
            'session_token' => 'session_token',
            'expires_at' => '2023-01-01 12:00:00'
        ];
        
        $session = UserSession::fromArray($data);
        
        $this->assertEquals(0, $session->getId());
        $this->assertEquals(1, $session->getUserId());
        $this->assertEquals('session_token', $session->getSessionToken());
        $this->assertNull($session->getIpAddress());
        $this->assertNull($session->getUserAgent());
    }

    /**
     * Тест проверки истечения срока действия UserSession
     */
    public function testUserSessionIsExpired(): void
    {
        $expiredAt = new DateTimeImmutable('-1 hour');
        $session = new UserSession(1, 'session_token', $expiredAt);
        
        $this->assertTrue($session->isExpired());
        $this->assertFalse($session->isActive());
    }

    /**
     * Тест проверки активности UserSession
     */
    public function testUserSessionIsActive(): void
    {
        $expiresAt = new DateTimeImmutable('+1 hour');
        $session = new UserSession(1, 'session_token', $expiresAt);
        
        $this->assertFalse($session->isExpired());
        $this->assertTrue($session->isActive());
    }

    /**
     * Тест продления срока действия UserSession
     */
    public function testUserSessionExtend(): void
    {
        $expiresAt = new DateTimeImmutable('+1 hour');
        $session = new UserSession(1, 'session_token', $expiresAt);
        
        $this->assertTrue($session->isActive());
        
        $session->extend(7200); // 2 hours
        
        $this->assertTrue($session->isActive());
        $this->assertGreaterThan($expiresAt, $session->getExpiresAt());
    }

    /**
     * Тест создания UserSession для пользователя
     */
    public function testUserSessionCreateForUser(): void
    {
        $session = UserSession::createForUser(1, 3600, '192.168.1.1', 'Mozilla/5.0');
        
        $this->assertEquals(1, $session->getUserId());
        $this->assertEquals('192.168.1.1', $session->getIpAddress());
        $this->assertEquals('Mozilla/5.0', $session->getUserAgent());
        $this->assertTrue($session->isActive());
        $this->assertNotEmpty($session->getSessionToken());
        $this->assertEquals(64, strlen($session->getSessionToken())); // 32 bytes = 64 hex chars
    }

    /**
     * Тест создания UserSession для пользователя с значениями по умолчанию
     */
    public function testUserSessionCreateForUserWithDefaults(): void
    {
        $session = UserSession::createForUser(1);
        
        $this->assertEquals(1, $session->getUserId());
        $this->assertNull($session->getIpAddress());
        $this->assertNull($session->getUserAgent());
        $this->assertTrue($session->isActive());
        $this->assertNotEmpty($session->getSessionToken());
    }

    /**
     * Тест обновления информации о сессии UserSession
     */
    public function testUserSessionUpdateSessionInfo(): void
    {
        $session = new UserSession(1, 'session_token', new DateTimeImmutable('+1 hour'));
        
        $this->assertNull($session->getIpAddress());
        $this->assertNull($session->getUserAgent());
        
        $session->updateSessionInfo('192.168.1.1', 'Mozilla/5.0');
        
        $this->assertEquals('192.168.1.1', $session->getIpAddress());
        $this->assertEquals('Mozilla/5.0', $session->getUserAgent());
    }

    /**
     * Тест частичного обновления информации о сессии UserSession
     */
    public function testUserSessionUpdateSessionInfoPartial(): void
    {
        $session = new UserSession(1, 'session_token', new DateTimeImmutable('+1 hour'), '192.168.1.1', 'Mozilla/5.0');
        
        $this->assertEquals('192.168.1.1', $session->getIpAddress());
        $this->assertEquals('Mozilla/5.0', $session->getUserAgent());
        
        $session->updateSessionInfo('192.168.1.2');
        
        $this->assertEquals('192.168.1.2', $session->getIpAddress());
        $this->assertEquals('Mozilla/5.0', $session->getUserAgent());
    }

    /**
     * Тест преобразования UserSession в массив
     */
    public function testUserSessionToArray(): void
    {
        $expiresAt = new DateTimeImmutable('2023-01-01 12:00:00');
        $session = new UserSession(1, 'session_token', $expiresAt, '192.168.1.1', 'Mozilla/5.0');
        
        $array = $session->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals(0, $array['id']);
        $this->assertEquals(1, $array['user_id']);
        $this->assertEquals('session_token', $array['session_token']);
        $this->assertEquals('192.168.1.1', $array['ip_address']);
        $this->assertEquals('Mozilla/5.0', $array['user_agent']);
        $this->assertEquals('2023-01-01 12:00:00', $array['expires_at']);
        $this->assertArrayHasKey('created_at', $array);
    }

    /**
     * Тест преобразования UserSession в массив с null значениями
     */
    public function testUserSessionToArrayWithNulls(): void
    {
        $expiresAt = new DateTimeImmutable('2023-01-01 12:00:00');
        $session = new UserSession(1, 'session_token', $expiresAt);
        
        $array = $session->toArray();
        
        $this->assertNull($array['ip_address']);
        $this->assertNull($array['user_agent']);
    }
}