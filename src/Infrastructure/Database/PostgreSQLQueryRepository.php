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

    public function save(Query $query): void
    {
        // TODO: Implement save() method.
    }

    public function findById(UuidInterface $id): ?Query
    {
        // TODO: Implement findById() method.
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
