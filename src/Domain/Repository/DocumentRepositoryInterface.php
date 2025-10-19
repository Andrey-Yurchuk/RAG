<?php

declare(strict_types=1);

namespace RagSystem\Domain\Repository;

use RagSystem\Domain\Model\Document;
use RagSystem\Domain\Model\DocumentChunk;
use Ramsey\Uuid\UuidInterface;

interface DocumentRepositoryInterface
{
    /**
     * Максимальное количество документов для методов с пагинацией
     */
    public const int DEFAULT_LIMIT = 10;

    /**
     * Смещение для методов с пагинацией
     */
    public const int DEFAULT_OFFSET = 0;

    /**
     * Порог сходства для векторного поиска (0.0 - 1.0)
     */
    public const float DEFAULT_SIMILARITY_THRESHOLD = 0.3;

    /**
     * Максимальное количество результатов для векторного поиска
     */
    public const int DEFAULT_SEARCH_LIMIT = 5;

    /**
     * Сохраняет документ в базу данных
     */
    public function save(Document $document): void;

    /**
     * Находит документ по его идентификатору
     */
    public function findById(UuidInterface $id): ?Document;

    /**
     * Получает список всех документов с пагинацией
     */
    public function findAll(int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array;

    /**
     * Удаляет документ по его идентификатору
     */
    public function delete(UuidInterface $id): bool;

    /**
     * Сохраняет фрагмент документа в хранилище
     */
    public function saveChunk(DocumentChunk $chunk): void;

    /**
     * Находит все фрагменты документа по идентификатору документа
     */
    public function findChunksByDocumentId(UuidInterface $documentId): array;

    /**
     * Ищет фрагменты документов, похожие на заданный запрос по векторному сходству
     */
    public function searchSimilarChunks(
        array $queryEmbedding,
        int $limit = self::DEFAULT_SEARCH_LIMIT,
        float $threshold = self::DEFAULT_SIMILARITY_THRESHOLD
    ): array;

    /**
     * Удаляет все фрагменты документа по идентификатору документа
     */
    public function deleteChunksByDocumentId(UuidInterface $documentId): bool;

    /**
     * Поиск чанков с использованием PostgreSQL Full-Text Search
     */
    public function searchByKeywords(string $query, int $limit = self::DEFAULT_SEARCH_LIMIT): array;

    /**
     * Гибридный поиск: комбинация векторного и ключевого поиска с RRF
     */
    public function searchHybrid(
        string $queryText,
        array $queryEmbedding,
        int $limit = self::DEFAULT_SEARCH_LIMIT,
        float $vectorThreshold = self::DEFAULT_SIMILARITY_THRESHOLD,
        int $vectorTopK = 20,
        int $keywordTopK = 20
    ): array;
}
