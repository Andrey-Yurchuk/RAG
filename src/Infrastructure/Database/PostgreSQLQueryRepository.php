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
    public function __construct(private Connection $connection, private LoggerInterface $logger){}

    /**
     * Сохраняет или обновляет запрос в базе данных PostgreSQL
     */
    public function save(Query $query): void
    {
        $data = [
            'id' => $query->getId()->toString(),
            'query_text' => $query->getQueryText(),
            'query_embedding' => $query->getQueryEmbedding() ? json_encode($query->getQueryEmbedding()) : null,
            'response' => $query->getResponse(),
            'created_at' => $query->getCreatedAt()->format('Y-m-d H:i:s'),
        ];

        $sql = '
            INSERT INTO queries (id, query_text, query_embedding, response, created_at)
            VALUES (:id, :query_text, :query_embedding::vector, :response, :created_at)
            ON CONFLICT (id) DO UPDATE SET
                query_text = EXCLUDED.query_text,
                query_embedding = EXCLUDED.query_embedding,
                response = EXCLUDED.response
        ';

        $this->connection->executeStatement($sql, $data);

        $this->logger->debug('Query saved', ['query_id' => $query->getId()->toString()]);
    }

    /**
     * Находит запрос по его идентификатору
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

    public function findAll(int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array
    {
        // TODO: Implement findAll() method.
    }

    public function findSimilarQueries(array $queryEmbedding, int $limit = self::DEFAULT_SIMILAR_SEARCH_LIMIT, float $threshold = self::DEFAULT_SIMILARITY_THRESHOLD): array
    {
        // TODO: Implement findSimilarQueries() method.
    }

    public function delete(UuidInterface $id): bool
    {
        // TODO: Implement delete() method.
    }
}
