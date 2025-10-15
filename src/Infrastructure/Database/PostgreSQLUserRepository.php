<?php

declare(strict_types=1);

namespace RagSystem\Infrastructure\Database;

use Doctrine\DBAL\Connection;
use RagSystem\Domain\Model\User;
use RagSystem\Domain\Repository\UserRepositoryInterface;
use ReflectionClass;

class PostgreSQLUserRepository implements UserRepositoryInterface
{
    public function __construct(private Connection $connection) {}

    /**
     * @inheritdoc
     */
    public function save(User $user): void
    {
        $data = $user->toArray();

        if ($data['id'] === 0) {
            unset($data['id']);
            $sql = '
                INSERT INTO users (username, email, password_hash, role, is_active, created_at, updated_at, last_login_at)
                VALUES (:username, :email, :password_hash, :role, :is_active, :created_at, :updated_at, :last_login_at)
            ';
            
            $this->connection->executeStatement($sql, $data);
            
            // Получаем сгенерированный ID
            $lastInsertId = $this->connection->lastInsertId();
            if ($lastInsertId) {
                // Обновляем ID в объекте пользователя
                $reflection = new ReflectionClass($user);
                $idProperty = $reflection->getProperty('id');
                $idProperty->setAccessible(true);
                $idProperty->setValue($user, (int) $lastInsertId);
            }
        } else {
            $sql = '
                INSERT INTO users (id, username, email, password_hash, role, is_active, created_at, updated_at, last_login_at)
                VALUES (:id, :username, :email, :password_hash, :role, :is_active, :created_at, :updated_at, :last_login_at)
                ON CONFLICT (id) DO UPDATE SET
                    username = EXCLUDED.username,
                    email = EXCLUDED.email,
                    password_hash = EXCLUDED.password_hash,
                    role = EXCLUDED.role,
                    is_active = EXCLUDED.is_active,
                    updated_at = EXCLUDED.updated_at,
                    last_login_at = EXCLUDED.last_login_at
            ';
            
            $this->connection->executeStatement($sql, $data);
        }
    }

    /**
     * @inheritdoc
     */
    public function findById(int $id): ?User
    {
        $sql = 'SELECT * FROM users WHERE id = :id';
        $result = $this->connection->fetchAssociative($sql, ['id' => $id]);

        return $result ? User::fromArray($result) : null;
    }

    /**
     * @inheritdoc
     */
    public function findByUsername(string $username): ?User
    {
        $sql = 'SELECT * FROM users WHERE username = :username';
        $result = $this->connection->fetchAssociative($sql, ['username' => $username]);

        return $result ? User::fromArray($result) : null;
    }

    /**
     * @inheritdoc
     */
    public function findByEmail(string $email): ?User
    {
        $sql = 'SELECT * FROM users WHERE email = :email';
        $result = $this->connection->fetchAssociative($sql, ['email' => $email]);

        return $result ? User::fromArray($result) : null;
    }

    /**
     * @inheritdoc
     */
    public function findAll(int $limit = 10, int $offset = 0): array
    {
        $sql = 'SELECT * FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
        $results = $this->connection->fetchAllAssociative($sql, [
            'limit' => $limit,
            'offset' => $offset
        ]);

        return array_map(fn($row) => User::fromArray($row), $results);
    }

    /**
     * @inheritdoc
     */
    public function findByRole(string $role, int $limit = 10, int $offset = 0): array
    {
        $sql = 'SELECT * FROM users WHERE role = :role ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
        $results = $this->connection->fetchAllAssociative($sql, [
            'role' => $role,
            'limit' => $limit,
            'offset' => $offset
        ]);

        return array_map(fn($row) => User::fromArray($row), $results);
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $id): void
    {
        $sql = 'DELETE FROM users WHERE id = :id';
        $this->connection->executeStatement($sql, ['id' => $id]);
    }

    /**
     * @inheritdoc
     */
    public function existsByUsername(string $username): bool
    {
        $sql = 'SELECT 1 FROM users WHERE username = :username LIMIT 1';
        $result = $this->connection->fetchOne($sql, ['username' => $username]);

        return $result !== false;
    }

    /**
     * @inheritdoc
     */
    public function existsByEmail(string $email): bool
    {
        $sql = 'SELECT 1 FROM users WHERE email = :email LIMIT 1';
        $result = $this->connection->fetchOne($sql, ['email' => $email]);

        return $result !== false;
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        $sql = 'SELECT COUNT(*) FROM users';
        return (int) $this->connection->fetchOne($sql);
    }

    /**
     * @inheritdoc
     */
    public function countByRole(string $role): int
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE role = :role';
        return (int) $this->connection->fetchOne($sql, ['role' => $role]);
    }
}
