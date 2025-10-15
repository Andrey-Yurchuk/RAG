<?php

declare(strict_types=1);

namespace RagSystem\Domain\Model;

use DateTimeImmutable;
use InvalidArgumentException;

final class User
{
    public const string ROLE_ADMIN = 'admin';
    public const string ROLE_USER = 'user';

    private int $id;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        private string $username,
        private string $email,
        private string $passwordHash,
        private string $role = self::ROLE_USER,
        private bool $isActive = true,
        private ?DateTimeImmutable $lastLoginAt = null
    ) {
        $this->id = 0;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Создает пользователя из массива данных
     */
    public static function fromArray(array $data): self
    {
        $user = new self(
            $data['username'],
            $data['email'],
            $data['password_hash'],
            $data['role'] ?? self::ROLE_USER,
            $data['is_active'] ?? true,
            isset($data['last_login_at']) ? new DateTimeImmutable($data['last_login_at']) : null
        );

        if (isset($data['id'])) {
            $user->id = (int) $data['id'];
        }

        if (isset($data['created_at'])) {
            $user->createdAt = new DateTimeImmutable($data['created_at']);
        }

        if (isset($data['updated_at'])) {
            $user->updatedAt = new DateTimeImmutable($data['updated_at']);
        }

        return $user;
    }

    /**
     * Проверяет, является ли пользователь администратором
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Проверяет, является ли пользователь обычным пользователем
     */
    public function isUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    /**
     * Проверяет, активен ли пользователь
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * Проверяет пароль пользователя
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    /**
     * Обновляет пароль пользователя
     */
    public function updatePassword(string $newPassword): void
    {
        $this->passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Обновляет время последнего входа
     */
    public function updateLastLogin(): void
    {
        $this->lastLoginAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Активирует пользователя
     */
    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Деактивирует пользователя
     */
    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Изменяет роль пользователя
     */
    public function changeRole(string $role): void
    {
        if (!in_array($role, [self::ROLE_ADMIN, self::ROLE_USER], true)) {
            throw new InvalidArgumentException("Invalid role: {$role}");
        }

        $this->role = $role;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Обновляет email пользователя
     */
    public function updateEmail(string $email): void
    {
        $this->email = $email;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Обновляет username пользователя
     */
    public function updateUsername(string $username): void
    {
        $this->username = $username;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Возвращает id пользователя
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Возвращает имя пользователя
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Возвращает email адрес пользователя
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Возвращает роль пользователя
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Возвращает дату создания пользователя
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Возвращает дату последнего обновления пользователя
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Возвращает дату последнего входа пользователя
     */
    public function getLastLoginAt(): ?DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    /**
     * Возвращает массив данных для сохранения в бд
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'password_hash' => $this->passwordHash,
            'role' => $this->role,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
            'last_login_at' => $this->lastLoginAt?->format('Y-m-d H:i:s'),
        ];
    }
}
