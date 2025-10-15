<?php

declare(strict_types=1);

namespace RagSystem\Domain\Repository;

use RagSystem\Domain\Model\UserSession;

interface UserSessionRepositoryInterface
{
    /**
     * Сохраняет сессию в бд
     */
    public function save(UserSession $session): void;

    /**
     * Находит сессию по токену
     */
    public function findByToken(string $token): ?UserSession;

    /**
     * Находит активные сессии пользователя
     */
    public function findActiveByUserId(int $userId): array;

    /**
     * Находит все сессии пользователя
     */
    public function findByUserId(int $userId): array;

    /**
     * Удаляет сессию по токену
     */
    public function deleteByToken(string $token): void;

    /**
     * Удаляет все сессии пользователя
     */
    public function deleteByUserId(int $userId): void;

    /**
     * Удаляет истекшие сессии
     */
    public function deleteExpired(): int;

    /**
     * Удаляет сессию по ID
     */
    public function deleteById(int $id): void;

    /**
     * Получает количество активных сессий пользователя
     */
    public function countActiveByUserId(int $userId): int;

    /**
     * Получает общее количество активных сессий
     */
    public function countActive(): int;
}
