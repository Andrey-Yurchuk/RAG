<?php

declare(strict_types=1);

namespace RagSystem\Domain\Repository;

use RagSystem\Domain\Model\User;

interface UserRepositoryInterface
{
    /**
     * Сохраняет пользователя в бд
     */
    public function save(User $user): void;

    /**
     * Находит пользователя по id
     */
    public function findById(int $id): ?User;

    /**
     * Находит пользователя по username
     */
    public function findByUsername(string $username): ?User;

    /**
     * Находит пользователя по email
     */
    public function findByEmail(string $email): ?User;

    /**
     * Получает всех пользователей с пагинацией
     */
    public function findAll(int $limit = 10, int $offset = 0): array;

    /**
     * Получает пользователей по роли
     */
    public function findByRole(string $role, int $limit = 10, int $offset = 0): array;

    /**
     * Удаляет пользователя по ID
     */
    public function deleteById(int $id): void;

    /**
     * Проверяет, существует ли пользователь с указанным username
     */
    public function existsByUsername(string $username): bool;

    /**
     * Проверяет, существует ли пользователь с указанным email
     */
    public function existsByEmail(string $email): bool;

    /**
     * Получает общее количество пользователей
     */
    public function count(): int;

    /**
     * Получает количество пользователей по роли
     */
    public function countByRole(string $role): int;
}
