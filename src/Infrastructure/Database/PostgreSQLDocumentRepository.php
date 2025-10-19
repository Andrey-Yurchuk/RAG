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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function saveChunk(DocumentChunk $chunk): void
    {
        $embedding = $chunk->getEmbedding();

        $embeddingString = null;
        if ($embedding) {
            $embeddingString = '[' . implode(',', $embedding) . ']';
        }

        $data = [
            'id' => $chunk->getId()->toString(),
            'document_id' => $chunk->getDocumentId()->toString(),
            'chunk_text' => $chunk->getChunkText(),
            'chunk_index' => $chunk->getChunkIndex(),
            'embedding' => $embeddingString,
            'created_at' => $chunk->getCreatedAt()->format('Y-m-d H:i:s'),
        ];

        $sql = '
            INSERT INTO document_chunks (id, document_id, chunk_text, chunk_index, embedding, created_at)
            VALUES (:id, :document_id, :chunk_text, :chunk_index, :embedding::vector, :created_at)
            ON CONFLICT (id) DO UPDATE SET
                chunk_text = EXCLUDED.chunk_text,
                chunk_index = EXCLUDED.chunk_index,
                embedding = EXCLUDED.embedding::vector
        ';

        $this->connection->executeStatement($sql, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function findChunksByDocumentId(UuidInterface $documentId): array
    {
        $sql = 'SELECT * FROM document_chunks WHERE document_id = :document_id ORDER BY chunk_index';
        $results = $this->connection->fetchAllAssociative($sql, [
            'document_id' => $documentId->toString()
        ]);

        $chunks = [];
        foreach ($results as $result) {
            if ($result['embedding']) {
                $embeddingString = trim($result['embedding'], '[]');
                $result['embedding'] = array_map('floatval', explode(',', $embeddingString));
            }
            $chunks[] = DocumentChunk::fromArray($result);
        }

        return $chunks;
    }

    /**
     * {@inheritdoc}
     */
    public function searchSimilarChunks(array $queryEmbedding, int $limit = 5, float $threshold = 0.3): array
    {
        $embeddingString = '[' . implode(',', $queryEmbedding) . ']';

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
            'embedding' => $embeddingString,
            'threshold' => $threshold,
            'limit' => $limit
        ]);

        $chunks = [];
        foreach ($results as $result) {
            if ($result['embedding']) {
                $embeddingString = trim($result['embedding'], '[]');
                $result['embedding'] = array_map('floatval', explode(',', $embeddingString));
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
     * {@inheritdoc}
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

    /**
     * {@inheritdoc}
     */
    public function searchByKeywords(string $query, int $limit = 5): array
    {
        $tsQuery = $this->prepareTsQuery($query);

        $sql = '
            SELECT 
                dc.*,
                d.title,
                d.file_path,
                ts_rank_cd(dc.search_vector, to_tsquery(\'russian\', :ts_query)) as rank
            FROM document_chunks dc
            JOIN documents d ON dc.document_id = d.id
            WHERE dc.search_vector @@ to_tsquery(\'russian\', :ts_query)
            ORDER BY rank DESC
            LIMIT :limit
        ';

        $results = $this->connection->fetchAllAssociative($sql, [
            'ts_query' => $tsQuery,
            'limit' => $limit
        ]);

        $chunks = [];
        foreach ($results as $result) {
            if ($result['embedding']) {
                $embeddingString = trim($result['embedding'], '[]');
                $result['embedding'] = array_map('floatval', explode(',', $embeddingString));
            }
            $chunks[] = array_merge($result, [
                'bm25_score' => (float)$result['rank']
            ]);
        }

        $this->logger->debug('Keyword search completed', [
            'results_count' => count($chunks),
            'query' => $query
        ]);

        return $chunks;
    }

    /**
     * {@inheritdoc}
     */
    public function searchHybrid(
        string $queryText,
        array $queryEmbedding,
        int $limit = 5,
        float $vectorThreshold = 0.3,
        int $vectorTopK = 20,
        int $keywordTopK = 20
    ): array {
        // 1. Векторный поиск (топ-K кандидатов)
        $vectorResults = $this->searchSimilarChunks(
            $queryEmbedding,
            $vectorTopK,
            $vectorThreshold
        );

        // 2. Ключевой поиск (топ-K кандидатов)
        $keywordResults = $this->searchByKeywords($queryText, $keywordTopK);

        // 3. Reciprocal Rank Fusion (RRF)
        $fusedResults = $this->reciprocalRankFusion(
            $vectorResults,
            $keywordResults,
            $limit
        );

        $this->logger->debug('Hybrid search completed', [
            'vector_count' => count($vectorResults),
            'keyword_count' => count($keywordResults),
            'fused_count' => count($fusedResults),
            'query' => $queryText
        ]);

        return $fusedResults;
    }

    /**
     * Reciprocal Rank Fusion для объединения результатов поиска
     */
    private function reciprocalRankFusion(
        array $vectorResults,
        array $keywordResults,
        int $limit,
        int $k = 60
    ): array {
        $scores = [];

        foreach ($vectorResults as $rank => $result) {
            $chunkId = $result['id'];
            $rrfScore = 1 / ($k + $rank + 1);

            if (!isset($scores[$chunkId])) {
                $scores[$chunkId] = [
                    'chunk' => $result,
                    'score' => 0,
                    'vector_rank' => $rank + 1,
                    'keyword_rank' => null
                ];
            }
            $scores[$chunkId]['score'] += $rrfScore;
        }

        foreach ($keywordResults as $rank => $result) {
            $chunkId = $result['id'];
            $rrfScore = 1 / ($k + $rank + 1);

            if (!isset($scores[$chunkId])) {
                $scores[$chunkId] = [
                    'chunk' => $result,
                    'score' => 0,
                    'vector_rank' => null,
                    'keyword_rank' => $rank + 1
                ];
            } else {
                $scores[$chunkId]['keyword_rank'] = $rank + 1;
            }
            $scores[$chunkId]['score'] += $rrfScore;
        }

        uasort($scores, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $results = [];
        $count = 0;
        foreach ($scores as $item) {
            if ($count >= $limit) {
                break;
            }
            $chunk = $item['chunk'];
            $chunk['hybrid_score'] = $item['score'];
            $chunk['vector_rank'] = $item['vector_rank'];
            $chunk['keyword_rank'] = $item['keyword_rank'];
            $results[] = $chunk;
            $count++;
        }

        return $results;
    }

    /**
     * Подготовка запроса для PostgreSQL tsquery
     */
    private function prepareTsQuery(string $query): string
    {
        $words = preg_split('/\s+/', mb_strtolower($query), -1, PREG_SPLIT_NO_EMPTY);

        if (empty($words)) {
            return '';
        }

        $stopWords = ['в', 'на', 'и', 'с', 'по', 'для', 'от', 'до', 'из', 'к', 'о', 'у'];

        $cleanWords = array_map(function ($word) use ($stopWords) {
            $word = preg_replace('/[^а-яёa-z0-9]/u', '', $word);

            if (!$word || mb_strlen($word) < 3 || in_array($word, $stopWords)) {
                return null;
            }

            return $word . ':*';
        }, $words);

        $cleanWords = array_filter($cleanWords);

        if (empty($cleanWords)) {
            return '';
        }

        return implode(' | ', $cleanWords);
    }
}
