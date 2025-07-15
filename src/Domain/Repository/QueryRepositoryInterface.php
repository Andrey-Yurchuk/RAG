<?php

declare(strict_types=1);

namespace RagSystem\Domain\Repository;

use RagSystem\Domain\Model\Query;
use Ramsey\Uuid\UuidInterface;

interface QueryRepositoryInterface
{
    /**
     * Максимальное количество запросов для методов с пагинацией
     */
    public const int DEFAULT_LIMIT = 10;

    /**
     * Смещение для методов с пагинацией
     */
    public const int DEFAULT_OFFSET = 0;

    /**
     * Порог сходства для поиска похожих запросов (0.0 - 1.0)
     */
    public const float DEFAULT_SIMILARITY_THRESHOLD = 0.9;

    /**
     * Максимальное количество результатов для поиска похожих запросов
     */
    public const int DEFAULT_SIMILAR_SEARCH_LIMIT = 5;

    /**
     * Сохраняет запрос в базу данных
     */
    public function save(Query $query): void;

    /**
     * Находит запрос по его идентификатору
     */
    public function findById(UuidInterface $id): ?Query;

    /**
     * Получает список всех запросов с пагинацией
     */
    public function findAll(int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array;

    /**
     * Ищет похожие запросы по векторному сходству
     */
    public function findSimilarQueries(
        array $queryEmbedding,
        int $limit = self::DEFAULT_SIMILAR_SEARCH_LIMIT,
        float $threshold = self::DEFAULT_SIMILARITY_THRESHOLD
    ): array;

    /**
     * Удаляет запрос по его идентификатору
     */
    public function delete(UuidInterface $id): bool;
}