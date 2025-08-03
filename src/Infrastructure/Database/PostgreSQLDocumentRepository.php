<?php

declare(strict_types=1);

namespace RagSystem\Infrastructure\Database;

use RagSystem\Domain\Model\Document;
use RagSystem\Domain\Model\DocumentChunk;
use RagSystem\Domain\Repository\DocumentRepositoryInterface;
use Ramsey\Uuid\UuidInterface;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class PostgreSQLDocumentRepository implements DocumentRepositoryInterface
{
    public function __construct(
        private Connection      $connection,
        private LoggerInterface $logger
    ){}

    /**
     * Сохраняет документ в базу данных (insert или update при конфликте)
     */
    public function save(Document $document): void
    {
        $data = [
            'id' => $document->getId()->toString(),
            'title' => $document->getTitle(),
            'content' => $document->getContent(),
            'file_path' => $document->getFilePath(),
            'file_type' => $document->getFileType(),
            'created_at' => $document->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $document->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];

        $sql = '
            INSERT INTO documents (id, title, content, file_path, file_type, created_at, updated_at)
            VALUES (:id, :title, :content, :file_path, :file_type, :created_at, :updated_at)
            ON CONFLICT (id) DO UPDATE SET
                title = EXCLUDED.title,
                content = EXCLUDED.content,
                file_path = EXCLUDED.file_path,
                file_type = EXCLUDED.file_type,
                updated_at = EXCLUDED.updated_at
        ';

        $this->connection->executeStatement($sql, $data);

        $this->logger->debug('Document saved', ['document_id' => $document->getId()->toString()]);
    }

    /**
     * Находит документ по его идентификатору
     */
    public function findById(UuidInterface $id): ?Document
    {
        $sql = 'SELECT * FROM documents WHERE id = :id';
        $result = $this->connection->fetchAssociative($sql, ['id' => $id->toString()]);

        if (!$result) {
            return null;
        }

        $document = Document::fromArray($result);

        $chunks = $this->findChunksByDocumentId($id);
        foreach ($chunks as $chunk) {
            $document->addChunk($chunk);
        }

        return $document;
    }

    public function findAll(int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array
    {
        // TODO: Implement findAll() method.
    }

    public function delete(UuidInterface $id): bool
    {
        // TODO: Implement delete() method.
    }

    public function saveChunk(DocumentChunk $chunk): void
    {
        // TODO: Implement saveChunk() method.
    }

    public function findChunksByDocumentId(UuidInterface $documentId): array
    {
        // TODO: Implement findChunksByDocumentId() method.
    }

    public function searchSimilarChunks(array $queryEmbedding, int $limit = self::DEFAULT_SEARCH_LIMIT, float $threshold = self::DEFAULT_SIMILARITY_THRESHOLD): array
    {
        // TODO: Implement searchSimilarChunks() method.
    }

    public function deleteChunksByDocumentId(UuidInterface $documentId): bool
    {
        // TODO: Implement deleteChunksByDocumentId() method.
    }
}