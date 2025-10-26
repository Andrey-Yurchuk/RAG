<?php

declare(strict_types=1);

namespace RagSystem\Infrastructure\Database;

use RagSystem\Domain\Model\Query;
use RagSystem\Domain\Repository\QueryRepositoryInterface;
use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Uuid;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class PostgreSQLQueryRepository implements QueryRepositoryInterface
{
    public function __construct(private Connection $connection, private LoggerInterface $logger)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function save(Query $query): void
    {
        $data = [
            'id' => $query->getId()->toString(),
            'query_text' => $query->getQueryText(),
            'query_embedding' => $query->getQueryEmbedding() ? json_encode($query->getQueryEmbedding()) : null,
            'response' => $query->getResponse(),
            'response_time' => $query->getResponseTime(),
            'created_at' => $query->getCreatedAt()->format('Y-m-d H:i:s'),
        ];

        $sql = '
            INSERT INTO queries (id, query_text, query_embedding, response, response_time, created_at)
            VALUES (:id, :query_text, :query_embedding::vector, :response, :response_time, :created_at)
            ON CONFLICT (id) DO UPDATE SET
                query_text = EXCLUDED.query_text,
                query_embedding = EXCLUDED.query_embedding,
                response = EXCLUDED.response,
                response_time = EXCLUDED.response_time
        ';

        $this->connection->executeStatement($sql, $data);

        $this->logger->debug('Query saved', ['query_id' => $query->getId()->toString()]);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(UuidInterface $id): ?Query
    {
        $sql = 'SELECT * FROM queries WHERE id = :id';
        $result = $this->connection->fetchAssociative($sql, ['id' => $id->toString()]);

        if (!$result) {
            return null;
        }

        // Преобразуем embedding из JSON строки обратно в массив
        if ($result['query_embedding']) {
            $result['query_embedding'] = json_decode($result['query_embedding'], true);
        }

        return Query::fromArray($result);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(int $limit = 10, int $offset = 0): array
    {
        $sql = 'SELECT * FROM queries ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
        $results = $this->connection->fetchAllAssociative($sql, [
            'limit' => $limit,
            'offset' => $offset
        ]);

        $queries = [];
        foreach ($results as $result) {
            // Преобразуем embedding из JSON строки обратно в массив
            if ($result['query_embedding']) {
                $result['query_embedding'] = json_decode($result['query_embedding'], true);
            }

            $queries[] = Query::fromArray($result);
        }

        return $queries;
    }

    /**
     * {@inheritdoc}
     */
    public function findSimilarQueries(array $queryEmbedding, int $limit = 5, float $threshold = 0.9): array
    {
        $embeddingJson = json_encode($queryEmbedding);

        $sql = '
            SELECT 
                *,
                (1 - (query_embedding <=> :embedding::vector)) as similarity
            FROM queries
            WHERE query_embedding IS NOT NULL
            AND (1 - (query_embedding <=> :embedding::vector)) >= :threshold
            ORDER BY similarity DESC
            LIMIT :limit
        ';

        $results = $this->connection->fetchAllAssociative($sql, [
            'embedding' => $embeddingJson,
            'threshold' => $threshold,
            'limit' => $limit
        ]);

        $queries = [];
        foreach ($results as $result) {
            if ($result['query_embedding']) {
                $result['query_embedding'] = json_decode($result['query_embedding'], true);
            }

            $queries[] = array_merge($result, [
                'similarity_score' => $result['similarity']
            ]);
        }

        $this->logger->debug('Similar queries search completed', [
            'results_count' => count($queries),
            'threshold' => $threshold
        ]);

        return $queries;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(UuidInterface $id): bool
    {
        $sql = 'DELETE FROM queries WHERE id = :id';
        $affectedRows = $this->connection->executeStatement($sql, ['id' => $id->toString()]);

        $success = $affectedRows > 0;

        if ($success) {
            $this->logger->debug('Query deleted', ['query_id' => $id->toString()]);
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        $sql = 'SELECT COUNT(*) FROM queries';
        return (int) $this->connection->fetchOne($sql);
    }
}
