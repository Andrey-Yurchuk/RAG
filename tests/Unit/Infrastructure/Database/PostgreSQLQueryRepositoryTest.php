<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Infrastructure\Database;

use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Infrastructure\Database\PostgreSQLQueryRepository;
use RagSystem\Domain\Model\Query;
use Ramsey\Uuid\Uuid;
use Mockery;

/**
 * @covers \RagSystem\Infrastructure\Database\PostgreSQLQueryRepository
 * @covers \RagSystem\Domain\Model\Query
 */
class PostgreSQLQueryRepositoryTest extends BaseTestCase
{
    private PostgreSQLQueryRepository $repository;
    private \Doctrine\DBAL\Connection $connection;
    private \Psr\Log\LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->connection = Mockery::mock(\Doctrine\DBAL\Connection::class);
        $this->logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        
        $this->repository = new PostgreSQLQueryRepository($this->connection, $this->logger);
    }

    /**
     * Тест сохранения запроса
     */
    public function testSaveQuery(): void
    {
        $query = new Query(
            'Тестовый запрос',
            [0.1, 0.2, 0.3],
            'Тестовый ответ',
            1.5
        );
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->once();
        
        $this->logger->shouldReceive('debug')
            ->with('Query saved', Mockery::type('array'))
            ->once();
        
        $this->repository->save($query);
        
        $this->assertTrue(true);
    }

    /**
     * Тест сохранения запроса без эмбеддинга
     */
    public function testSaveQueryWithoutEmbedding(): void
    {
        $query = new Query(
            'Тестовый запрос',
            null,
            'Тестовый ответ',
            1.5
        );
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->once();
        
        $this->logger->shouldReceive('debug')
            ->with('Query saved', Mockery::type('array'))
            ->once();
        
        $this->repository->save($query);
        
        $this->assertTrue(true);
    }

    /**
     * Тест поиска запроса по ID
     */
    public function testFindQueryById(): void
    {
        $id = Uuid::uuid4();
        $expectedData = [
            'id' => $id->toString(),
            'query_text' => 'Тестовый запрос',
            'query_embedding' => '[0.1,0.2,0.3]',
            'response' => 'Тестовый ответ',
            'response_time' => 1.5,
            'created_at' => '2023-01-01 12:00:00'
        ];
        
        $this->connection->shouldReceive('fetchAssociative')
            ->with(Mockery::type('string'), ['id' => $id->toString()])
            ->andReturn($expectedData)
            ->once();
        
        $result = $this->repository->findById($id);
        
        $this->assertInstanceOf(Query::class, $result);
        $this->assertEquals('Тестовый запрос', $result->getQueryText());
        $this->assertEquals('Тестовый ответ', $result->getResponse());
    }

    /**
     * Тест поиска запроса по ID (не найден)
     */
    public function testFindQueryByIdNotFound(): void
    {
        $id = Uuid::uuid4();
        
        $this->connection->shouldReceive('fetchAssociative')
            ->with(Mockery::type('string'), ['id' => $id->toString()])
            ->andReturn(false)
            ->once();
        
        $result = $this->repository->findById($id);
        
        $this->assertNull($result);
    }

    /**
     * Тест поиска всех запросов
     */
    public function testFindAllQueries(): void
    {
        $expectedData = [
            [
                'id' => '123e4567-e89b-12d3-a456-426614174000',
                'query_text' => 'Запрос 1',
                'query_embedding' => '[0.1,0.2,0.3]',
                'response' => 'Ответ 1',
                'response_time' => 1.5,
                'created_at' => '2023-01-01 12:00:00'
            ],
            [
                'id' => '123e4567-e89b-12d3-a456-426614174001',
                'query_text' => 'Запрос 2',
                'query_embedding' => '[0.4,0.5,0.6]',
                'response' => 'Ответ 2',
                'response_time' => 2.0,
                'created_at' => '2023-01-02 12:00:00'
            ]
        ];
        
        $this->connection->shouldReceive('fetchAllAssociative')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->andReturn($expectedData)
            ->once();
        
        $result = $this->repository->findAll();
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Query::class, $result[0]);
        $this->assertInstanceOf(Query::class, $result[1]);
    }

    /**
     * Тест поиска всех запросов с параметрами
     */
    public function testFindAllQueriesWithParams(): void
    {
        $expectedData = [
            [
                'id' => '123e4567-e89b-12d3-a456-426614174000',
                'query_text' => 'Запрос 1',
                'query_embedding' => '[0.1,0.2,0.3]',
                'response' => 'Ответ 1',
                'response_time' => 1.5,
                'created_at' => '2023-01-01 12:00:00'
            ]
        ];
        
        $this->connection->shouldReceive('fetchAllAssociative')
            ->with(Mockery::type('string'), ['limit' => 5, 'offset' => 10])
            ->andReturn($expectedData)
            ->once();
        
        $result = $this->repository->findAll(5, 10);
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Query::class, $result[0]);
    }

    /**
     * Тест поиска похожих запросов
     */
    public function testFindSimilarQueries(): void
    {
        $queryEmbedding = [0.1, 0.2, 0.3];
        $expectedData = [
            [
                'id' => '123e4567-e89b-12d3-a456-426614174000',
                'query_text' => 'Похожий запрос',
                'query_embedding' => '[0.1,0.2,0.3]',
                'response' => 'Ответ',
                'response_time' => 1.5,
                'created_at' => '2023-01-01 12:00:00',
                'similarity' => 0.95
            ]
        ];
        
        $this->connection->shouldReceive('fetchAllAssociative')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->andReturn($expectedData)
            ->once();
        
        $this->logger->shouldReceive('debug')
            ->with('Similar queries search completed', Mockery::type('array'))
            ->once();
        
        $result = $this->repository->findSimilarQueries($queryEmbedding);
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('similarity_score', $result[0]);
        $this->assertEquals(0.95, $result[0]['similarity_score']);
    }

    /**
     * Тест поиска похожих запросов с параметрами
     */
    public function testFindSimilarQueriesWithParams(): void
    {
        $queryEmbedding = [0.1, 0.2, 0.3];
        $expectedData = [];
        
        $this->connection->shouldReceive('fetchAllAssociative')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->andReturn($expectedData)
            ->once();
        
        $this->logger->shouldReceive('debug')
            ->with('Similar queries search completed', Mockery::type('array'))
            ->once();
        
        $result = $this->repository->findSimilarQueries($queryEmbedding, 10, 0.8);
        
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    /**
     * Тест удаления запроса
     */
    public function testDeleteQuery(): void
    {
        $id = Uuid::uuid4();
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), ['id' => $id->toString()])
            ->andReturn(1)
            ->once();
        
        $this->logger->shouldReceive('debug')
            ->with('Query deleted', Mockery::type('array'))
            ->once();
        
        $result = $this->repository->delete($id);
        
        $this->assertTrue($result);
    }

    /**
     * Тест удаления несуществующего запроса
     */
    public function testDeleteNonExistentQuery(): void
    {
        $id = Uuid::uuid4();
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), ['id' => $id->toString()])
            ->andReturn(0)
            ->once();
        
        $result = $this->repository->delete($id);
        
        $this->assertFalse($result);
    }

    /**
     * Тест с русскими символами
     */
    public function testWithRussianText(): void
    {
        $query = new Query(
            'Тестовый запрос на русском языке',
            [0.1, 0.2, 0.3],
            'Ответ на русском языке',
            1.5
        );
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->once();
        
        $this->logger->shouldReceive('debug')
            ->with('Query saved', Mockery::type('array'))
            ->once();
        
        $this->repository->save($query);
        
        $this->assertTrue(true);
    }

    /**
     * Тест с пустым ответом
     */
    public function testWithEmptyResponse(): void
    {
        $query = new Query(
            'Тестовый запрос',
            [0.1, 0.2, 0.3],
            '',
            1.5
        );
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->once();
        
        $this->logger->shouldReceive('debug')
            ->with('Query saved', Mockery::type('array'))
            ->once();
        
        $this->repository->save($query);
        
        $this->assertTrue(true);
    }

    /**
     * Тест с нулевым временем ответа
     */
    public function testWithZeroResponseTime(): void
    {
        $query = new Query(
            'Тестовый запрос',
            [0.1, 0.2, 0.3],
            'Ответ',
            0.0
        );
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->once();
        
        $this->logger->shouldReceive('debug')
            ->with('Query saved', Mockery::type('array'))
            ->once();
        
        $this->repository->save($query);
        
        $this->assertTrue(true);
    }

    /**
     * Тест с большим временем ответа
     */
    public function testWithLargeResponseTime(): void
    {
        $query = new Query(
            'Тестовый запрос',
            [0.1, 0.2, 0.3],
            'Ответ',
            999.99
        );
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->once();
        
        $this->logger->shouldReceive('debug')
            ->with('Query saved', Mockery::type('array'))
            ->once();
        
        $this->repository->save($query);
        
        $this->assertTrue(true);
    }

    /**
     * Тест с большим эмбеддингом
     */
    public function testWithLargeEmbedding(): void
    {
        $largeEmbedding = array_fill(0, 1000, 0.1);
        $query = new Query(
            'Тестовый запрос',
            $largeEmbedding,
            'Ответ',
            1.5
        );
        
        $this->connection->shouldReceive('executeStatement')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->once();
        
        $this->logger->shouldReceive('debug')
            ->with('Query saved', Mockery::type('array'))
            ->once();
        
        $this->repository->save($query);
        
        $this->assertTrue(true);
    }
}
