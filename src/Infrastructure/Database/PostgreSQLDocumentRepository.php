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
        private Connection $connection,
        private LoggerInterface $logger
    ) {
    }

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

    /**
     * Возвращает список всех документов с пагинацией
     */
    public function findAll(int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array
    {
        $sql = 'SELECT * FROM documents ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
        $results = $this->connection->fetchAllAssociative($sql, [
            'limit' => $limit,
            'offset' => $offset
        ]);

        $documents = [];
        foreach ($results as $result) {
            $documents[] = Document::fromArray($result);
        }

        return $documents;
    }

    /**
     * Удаляет документ
     */
    public function delete(UuidInterface $id): bool
    {
        $sql = 'DELETE FROM documents WHERE id = :id';
        $affectedRows = $this->connection->executeStatement($sql, ['id' => $id->toString()]);

        $success = $affectedRows > 0;

        if ($success) {
            $this->logger->debug('Document deleted', ['document_id' => $id->toString()]);
        }

        return $success;
    }

    /**
     * Сохраняет фрагмент (чанк) документа в базу данных (insert или update при конфликте)
     */
    public function saveChunk(DocumentChunk $chunk): void
    {
        $data = [
            'id' => $chunk->getId()->toString(),
            'document_id' => $chunk->getDocumentId()->toString(),
            'chunk_text' => $chunk->getChunkText(),
            'chunk_index' => $chunk->getChunkIndex(),
            'embedding' => $chunk->getEmbedding() ? json_encode($chunk->getEmbedding()) : null,
            'created_at' => $chunk->getCreatedAt()->format('Y-m-d H:i:s'),
        ];

        $sql = '
            INSERT INTO document_chunks (id, document_id, chunk_text, chunk_index, embedding, created_at)
            VALUES (:id, :document_id, :chunk_text, :chunk_index, :embedding::vector, :created_at)
            ON CONFLICT (id) DO UPDATE SET
                chunk_text = EXCLUDED.chunk_text,
                chunk_index = EXCLUDED.chunk_index,
                embedding = EXCLUDED.embedding
        ';

        $this->connection->executeStatement($sql, $data);
    }

    /**
     * Находит все фрагменты (чанки) документа по его идентификатору
     */
    public function findChunksByDocumentId(UuidInterface $documentId): array
    {
        $sql = 'SELECT * FROM document_chunks WHERE document_id = :document_id ORDER BY chunk_index';
        $results = $this->connection->fetchAllAssociative($sql, [
            'document_id' => $documentId->toString()
        ]);

        $chunks = [];
        foreach ($results as $result) {
            // Преобразуем embedding из JSON-строки обратно в массив
            if ($result['embedding']) {
                $result['embedding'] = json_decode($result['embedding'], true, 512, JSON_THROW_ON_ERROR);
            }

            $chunks[] = DocumentChunk::fromArray($result);
        }

        return $chunks;
    }

    /**
     * Ищет похожие фрагменты (чанки) документов по векторному представлению запроса
     */
    public function searchSimilarChunks(array $queryEmbedding, int $limit = 10, float $threshold = 0.8): array
    {
        $embeddingJson = json_encode($queryEmbedding, JSON_THROW_ON_ERROR);

        $sql = '
            SELECT 
                dc.*,
                d.title,
                d.file_path,
                (1 - (embedding <=> :embedding::vector)) as similarity
            FROM document_chunks dc
            JOIN documents d ON dc.document_id = d.id
            WHERE dc.embedding IS NOT NULL
            AND (1 - (embedding <=> :embedding::vector)) >= :threshold
            ORDER BY similarity DESC
            LIMIT :limit
        ';

        $results = $this->connection->fetchAllAssociative($sql, [
            'embedding' => $embeddingJson,
            'threshold' => $threshold,
            'limit' => $limit
        ]);

        $chunks = [];
        foreach ($results as $result) {
            if ($result['embedding']) {
                $result['embedding'] = json_decode($result['embedding'], true);
            }

            $chunks[] = array_merge($result, [
                'similarity_score' => $result['similarity']
            ]);
        }

        $this->logger->debug('Similar chunks search completed', [
            'results_count' => count($chunks),
            'threshold' => $threshold
        ]);

        return $chunks;
    }

    /**
     * Удаляет все фрагменты документа по идентификатору документа
     */
    public function deleteChunksByDocumentId(UuidInterface $documentId): bool
    {
        $sql = 'DELETE FROM document_chunks WHERE document_id = :document_id';
        $affectedRows = $this->connection->executeStatement($sql, [
            'document_id' => $documentId->toString()
        ]);

        $this->logger->debug('Document chunks deleted', [
            'document_id' => $documentId->toString(),
            'deleted_count' => $affectedRows
        ]);

        return $affectedRows > 0;
    }
}
