<?php

declare(strict_types=1);

namespace RagSystem\Infrastructure\Database;

use Doctrine\DBAL\Connection;
use RagSystem\Domain\Model\UserSession;
use RagSystem\Domain\Repository\UserSessionRepositoryInterface;
use ReflectionClass;

class PostgreSQLUserSessionRepository implements UserSessionRepositoryInterface
{
    public function __construct(private Connection $connection)
    {
    }

    /**
     * @inheritdoc
     */
    public function save(UserSession $session): void
    {
        $data = $session->toArray();

        if ($data['id'] === 0) {
            unset($data['id']);
            $sql = '
                INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at, created_at)
                VALUES (:user_id, :session_token, :ip_address, :user_agent, :expires_at, :created_at)
            ';

            $this->connection->executeStatement($sql, $data);

            // Получаем сгенерированный ID
            $lastInsertId = $this->connection->lastInsertId();
            if ($lastInsertId) {
                // Обновляем ID в объекте сессии
                $reflection = new ReflectionClass($session);
                $idProperty = $reflection->getProperty('id');
                $idProperty->setAccessible(true);
                $idProperty->setValue($session, (int) $lastInsertId);
            }
        } else {
            $sql = '
                INSERT INTO user_sessions (id, user_id, session_token, ip_address, user_agent, expires_at, created_at)
                VALUES (:id, :user_id, :session_token, :ip_address, :user_agent, :expires_at, :created_at)
                ON CONFLICT (id) DO UPDATE SET
                    session_token = EXCLUDED.session_token,
                    ip_address = EXCLUDED.ip_address,
                    user_agent = EXCLUDED.user_agent,
                    expires_at = EXCLUDED.expires_at
            ';

            $this->connection->executeStatement($sql, $data);
        }
    }

    /**
     * @inheritdoc
     */
    public function findByToken(string $token): ?UserSession
    {
        $sql = 'SELECT * FROM user_sessions WHERE session_token = :token';
        $result = $this->connection->fetchAssociative($sql, ['token' => $token]);

        return $result ? UserSession::fromArray($result) : null;
    }

    /**
     * @inheritdoc
     */
    public function findActiveByUserId(int $userId): array
    {
        $sql = 'SELECT * FROM user_sessions WHERE user_id = :user_id AND expires_at > NOW() ORDER BY created_at DESC';
        $results = $this->connection->fetchAllAssociative($sql, ['user_id' => $userId]);

        return array_map(fn($row) => UserSession::fromArray($row), $results);
    }

    /**
     * @inheritdoc
     */
    public function findByUserId(int $userId): array
    {
        $sql = 'SELECT * FROM user_sessions WHERE user_id = :user_id ORDER BY created_at DESC';
        $results = $this->connection->fetchAllAssociative($sql, ['user_id' => $userId]);

        return array_map(fn($row) => UserSession::fromArray($row), $results);
    }

    /**
     * @inheritdoc
     */
    public function deleteByToken(string $token): void
    {
        $sql = 'DELETE FROM user_sessions WHERE session_token = :token';
        $this->connection->executeStatement($sql, ['token' => $token]);
    }

    /**
     * @inheritdoc
     */
    public function deleteByUserId(int $userId): void
    {
        $sql = 'DELETE FROM user_sessions WHERE user_id = :user_id';
        $this->connection->executeStatement($sql, ['user_id' => $userId]);
    }

    /**
     * @inheritdoc
     */
    public function deleteExpired(): int
    {
        $sql = 'DELETE FROM user_sessions WHERE expires_at <= NOW()';
        return $this->connection->executeStatement($sql);
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $id): void
    {
        $sql = 'DELETE FROM user_sessions WHERE id = :id';
        $this->connection->executeStatement($sql, ['id' => $id]);
    }

    /**
     * @inheritdoc
     */
    public function countActiveByUserId(int $userId): int
    {
        $sql = 'SELECT COUNT(*) FROM user_sessions WHERE user_id = :user_id AND expires_at > NOW()';
        return (int) $this->connection->fetchOne($sql, ['user_id' => $userId]);
    }

    /**
     * @inheritdoc
     */
    public function countActive(): int
    {
        $sql = 'SELECT COUNT(*) FROM user_sessions WHERE expires_at > NOW()';
        return (int) $this->connection->fetchOne($sql);
    }
}
