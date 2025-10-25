<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Infrastructure\Database;

use Exception;
use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Infrastructure\Database\PostgreSQLDocumentRepository;
use RagSystem\Domain\Model\Document;
use RagSystem\Domain\Model\DocumentChunk;
use Ramsey\Uuid\Uuid;
use Mockery;

/**
 * Тесты для PostgreSQLDocumentRepository
 * 
 * @covers \RagSystem\Infrastructure\Database\PostgreSQLDocumentRepository
 * @covers \RagSystem\Domain\Model\Document
 * @covers \RagSystem\Domain\Model\DocumentChunk
 */
class PostgreSQLDocumentRepositoryTest extends BaseTestCase
{
    private PostgreSQLDocumentRepository $repository;
    private \Doctrine\DBAL\Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = Mockery::mock(\Doctrine\DBAL\Connection::class);
        $this->repository = new PostgreSQLDocumentRepository($this->connection, $this->createMockLogger());
    }

    /**
     * Тест сохранения документа
     */
    public function testSaveDocument(): void
    {
        $document = new Document(
            'Тестовый документ', 
            'Содержимое документа',
            '/test/path/document.txt',
            'txt'
        );

        $this->connection->shouldReceive('executeStatement')
            ->once()
            ->with(
                Mockery::pattern('/INSERT INTO documents/'),
                Mockery::type('array')
            )
            ->andReturn(1);

        $this->repository->save($document);

        $this->assertTrue(true);
    }

    /**
     * Тест поиска документа по ID
     */
    public function testFindDocumentById(): void
    {
        $documentId = Uuid::uuid4();
        $expectedData = [
            'id' => $documentId->toString(),
            'title' => 'Найденный документ',
            'content' => 'Содержимое найденного документа',
            'file_path' => '/test/path/document.txt',
            'file_type' => 'txt',
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ];

        $this->connection->shouldReceive('fetchAssociative')
            ->once()
            ->with(
                Mockery::pattern('/SELECT.*FROM documents/'),
                Mockery::type('array')
            )
            ->andReturn($expectedData);

        $this->connection->shouldReceive('fetchAllAssociative')
            ->once()
            ->with(
                Mockery::pattern('/SELECT.*FROM document_chunks/'),
                Mockery::type('array')
            )
            ->andReturn([]);

        $document = $this->repository->findById($documentId);

        $this->assertInstanceOf(Document::class, $document);
        $this->assertEquals($expectedData['title'], $document->getTitle());
        $this->assertEquals($expectedData['content'], $document->getContent());
    }

    /**
     * Тест поиска несуществующего документа
     */
    public function testFindNonExistentDocument(): void
    {
        $documentId = Uuid::uuid4();

        $this->connection->shouldReceive('fetchAssociative')
            ->once()
            ->with(
                Mockery::pattern('/SELECT.*FROM documents/'),
                Mockery::type('array')
            )
            ->andReturn(false);

        $document = $this->repository->findById($documentId);

        $this->assertNull($document);
    }

    /**
     * Тест получения всех документов
     */
    public function testFindAllDocuments(): void
    {
        $documentsData = [
            [
                'id' => Uuid::uuid4()->toString(),
                'title' => 'Документ 1',
                'content' => 'Содержимое 1',
                'file_path' => '/test/path/doc1.txt',
                'file_type' => 'txt',
                'created_at' => '2024-01-01 12:00:00',
                'updated_at' => '2024-01-01 12:00:00'
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'title' => 'Документ 2',
                'content' => 'Содержимое 2',
                'file_path' => '/test/path/doc2.txt',
                'file_type' => 'txt',
                'created_at' => '2024-01-01 12:00:00',
                'updated_at' => '2024-01-01 12:00:00'
            ]
        ];

        $this->connection->shouldReceive('fetchAllAssociative')
            ->once()
            ->with(
                Mockery::pattern('/SELECT.*FROM documents/'),
                Mockery::type('array')
            )
            ->andReturn($documentsData);

        $documents = $this->repository->findAll();

        $this->assertCount(2, $documents);
        $this->assertInstanceOf(Document::class, $documents[0]);
        $this->assertInstanceOf(Document::class, $documents[1]);
    }

    /**
     * Тест удаления документа
     */
    public function testDeleteDocument(): void
    {
        $documentId = Uuid::uuid4();

        $this->connection->shouldReceive('executeStatement')
            ->once()
            ->with(
                Mockery::pattern('/DELETE FROM documents/'),
                Mockery::type('array')
            )
            ->andReturn(1);

        $result = $this->repository->delete($documentId);

        $this->assertTrue($result);
    }

    /**
     * Тест сохранения фрагмента документа
     */
    public function testSaveDocumentChunk(): void
    {
        $documentId = Uuid::uuid4();
        $chunk = new DocumentChunk($documentId, 'Фрагмент текста', 0);
        $chunk->setEmbedding([0.1, 0.2, 0.3]);

        $this->connection->shouldReceive('executeStatement')
            ->once()
            ->with(
                Mockery::pattern('/INSERT INTO document_chunks/'),
                Mockery::type('array')
            )
            ->andReturn(1);

        $this->repository->saveChunk($chunk);

        $this->assertTrue(true);
    }

    /**
     * Тест поиска фрагментов документа
     */
    public function testFindChunksByDocumentId(): void
    {
        $documentId = Uuid::uuid4();
        $chunksData = [
            [
                'id' => Uuid::uuid4()->toString(),
                'document_id' => $documentId->toString(),
                'chunk_text' => 'Фрагмент 1',
                'chunk_index' => 0,
                'embedding' => '[0.1,0.2,0.3]',
                'created_at' => '2024-01-01 12:00:00'
            ]
        ];

        $this->connection->shouldReceive('fetchAllAssociative')
            ->once()
            ->with(
                Mockery::pattern('/SELECT.*FROM document_chunks/'),
                Mockery::type('array')
            )
            ->andReturn($chunksData);

        $chunks = $this->repository->findChunksByDocumentId($documentId);

        $this->assertCount(1, $chunks);
        $this->assertInstanceOf(DocumentChunk::class, $chunks[0]);
    }

    /**
     * Тест поиска похожих фрагментов
     */
    public function testSearchSimilarChunks(): void
    {
        $queryEmbedding = [0.1, 0.2, 0.3];
        $limit = 5;
        $similarityThreshold = 0.8;

        $expectedResults = [
            [
                'id' => 'chunk-1',
                'chunk_text' => 'Похожий фрагмент 1',
                'similarity' => 0.9,
                'embedding' => '[0.1,0.2,0.3]'
            ]
        ];

        $this->connection->shouldReceive('fetchAllAssociative')
            ->once()
            ->with(
                Mockery::type('string'),
                Mockery::type('array')
            )
            ->andReturn($expectedResults);

        $results = $this->repository->searchSimilarChunks($queryEmbedding, $limit, $similarityThreshold);

        $this->assertCount(1, $results);
        $this->assertEquals('Похожий фрагмент 1', $results[0]['chunk_text']);
    }

    /**
     * Тест обработки ошибки базы данных
     */
    public function testDatabaseError(): void
    {
        $this->connection->shouldReceive('executeStatement')
            ->once()
            ->andThrow(new Exception('Ошибка подключения к БД'));

        $document = new Document('Тест', 'Содержимое');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Ошибка подключения к БД');

        $this->repository->save($document);
    }


    /**
     * Тест поиска по ключевым словам
     */
    public function testSearchByKeywords(): void
    {
        $query = 'тестовый запрос';
        $limit = 5;
        $expectedResults = [
            [
                'id' => 'doc-1',
                'title' => 'Тестовый документ',
                'content' => 'Содержимое документа',
                'similarity' => 0.9,
                'embedding' => '[0.1,0.2,0.3]',
                'rank' => 1
            ]
        ];

        $this->connection->shouldReceive('fetchAllAssociative')
            ->once()
            ->with(
                Mockery::type('string'),
                Mockery::type('array')
            )
            ->andReturn($expectedResults);

        $results = $this->repository->searchByKeywords($query, $limit);

        $this->assertCount(1, $results);
        $this->assertEquals('Тестовый документ', $results[0]['title']);
    }

    /**
     * Тест поиска по ключевым словам с дефолтными параметрами
     */
    public function testSearchByKeywordsWithDefaults(): void
    {
        $query = 'поиск';
        $expectedResults = [];

        $this->connection->shouldReceive('fetchAllAssociative')
            ->once()
            ->andReturn($expectedResults);

        $results = $this->repository->searchByKeywords($query);

        $this->assertEmpty($results);
    }

    /**
     * Тест гибридного поиска
     */
    public function testSearchHybrid(): void
    {
        $queryText = 'гибридный поиск';
        $queryEmbedding = [0.1, 0.2, 0.3];
        $limit = 10;
        $threshold = 0.8;
        $vectorTopK = 5;
        $keywordTopK = 5;
        $expectedResults = [
            [
                'chunk_text' => 'Релевантный фрагмент',
                'similarity' => 0.9,
                'bm25_score' => 0.8,
                'embedding' => '[0.1,0.2,0.3]',
                'rank' => 1,
                'id' => 'chunk-1'
            ]
        ];

        $this->connection->shouldReceive('fetchAllAssociative')
            ->twice() // Один раз для searchSimilarChunks, один раз для searchByKeywords
            ->andReturn($expectedResults);

        $results = $this->repository->searchHybrid(
            $queryText,
            $queryEmbedding,
            $limit,
            $threshold,
            $vectorTopK,
            $keywordTopK
        );

        $this->assertCount(1, $results);
        $this->assertEquals('Релевантный фрагмент', $results[0]['chunk_text']);
    }

    /**
     * Тест гибридного поиска с дефолтными параметрами
     */
    public function testSearchHybridWithDefaults(): void
    {
        $queryText = 'поиск';
        $queryEmbedding = [0.1, 0.2, 0.3];
        $expectedResults = [];

        // searchHybrid вызывает searchSimilarChunks и searchByKeywords
        $this->connection->shouldReceive('fetchAllAssociative')
            ->twice() // Один раз для searchSimilarChunks, один раз для searchByKeywords
            ->andReturn($expectedResults);

        $results = $this->repository->searchHybrid($queryText, $queryEmbedding);

        $this->assertEmpty($results);
    }

    /**
     * Тест удаления фрагментов документа
     */
    public function testDeleteChunksByDocumentId(): void
    {
        $documentId = Uuid::uuid4();

        $this->connection->shouldReceive('executeStatement')
            ->once()
            ->with(
                Mockery::pattern('/DELETE FROM document_chunks/'),
                Mockery::type('array')
            )
            ->andReturn(3);

        $result = $this->repository->deleteChunksByDocumentId($documentId);

        $this->assertTrue($result);
    }

    /**
     * Тест удаления фрагментов несуществующего документа
     */
    public function testDeleteChunksByNonExistentDocumentId(): void
    {
        $documentId = Uuid::uuid4();

        $this->connection->shouldReceive('executeStatement')
            ->once()
            ->andReturn(0);

        $result = $this->repository->deleteChunksByDocumentId($documentId);

        $this->assertFalse($result);
    }

    /**
     * Тест поиска фрагментов с пустым результатом
     */
    public function testSearchSimilarChunksWithEmptyResult(): void
    {
        $queryEmbedding = [0.1, 0.2, 0.3];
        $limit = 5;
        $similarityThreshold = 0.8;

        $this->connection->shouldReceive('fetchAllAssociative')
            ->once()
            ->andReturn([]);

        $results = $this->repository->searchSimilarChunks($queryEmbedding, $limit, $similarityThreshold);

        $this->assertEmpty($results);
    }

    /**
     * Тест гибридного поиска с различными результатами для покрытия reciprocalRankFusion
     */
    public function testSearchHybridWithDifferentResults(): void
    {
        $queryText = 'тестовый запрос';
        $queryEmbedding = [0.1, 0.2, 0.3];
        $limit = 3;
        $threshold = 0.8;
        $vectorTopK = 2;
        $keywordTopK = 2;

        // Разные результаты для векторного и ключевого поиска
        $vectorResults = [
            [
                'id' => 'chunk-1',
                'chunk_text' => 'Векторный результат 1',
                'similarity' => 0.9,
                'embedding' => '[0.1,0.2,0.3]'
            ],
            [
                'id' => 'chunk-2', 
                'chunk_text' => 'Векторный результат 2',
                'similarity' => 0.8,
                'embedding' => '[0.2,0.3,0.4]'
            ]
        ];

        $keywordResults = [
            [
                'id' => 'chunk-2',
                'chunk_text' => 'Ключевой результат 1',
                'bm25_score' => 0.7,
                'rank' => 1,
                'embedding' => '[0.2,0.3,0.4]'
            ],
            [
                'id' => 'chunk-3',
                'chunk_text' => 'Ключевой результат 2', 
                'bm25_score' => 0.6,
                'rank' => 2,
                'embedding' => '[0.3,0.4,0.5]'
            ]
        ];

        // searchHybrid вызывает searchSimilarChunks и searchByKeywords
        $this->connection->shouldReceive('fetchAllAssociative')
            ->twice()
            ->andReturn($vectorResults, $keywordResults);

        $results = $this->repository->searchHybrid(
            $queryText,
            $queryEmbedding,
            $limit,
            $threshold,
            $vectorTopK,
            $keywordTopK
        );

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);

        // Проверяем, что результаты содержат hybrid_score
        foreach ($results as $result) {
            $this->assertArrayHasKey('hybrid_score', $result);
            $this->assertArrayHasKey('vector_rank', $result);
            $this->assertArrayHasKey('keyword_rank', $result);
        }
    }

    /**
     * Тест гибридного поиска с пустыми результатами
     */
    public function testSearchHybridWithEmptyResults(): void
    {
        $queryText = 'пустой запрос';
        $queryEmbedding = [0.1, 0.2, 0.3];

        $this->connection->shouldReceive('fetchAllAssociative')
            ->twice()
            ->andReturn([], []);

        $results = $this->repository->searchHybrid($queryText, $queryEmbedding);

        $this->assertEmpty($results);
    }

    /**
     * Тест гибридного поиска с одинаковыми результатами
     */
    public function testSearchHybridWithSameResults(): void
    {
        $queryText = 'одинаковый запрос';
        $queryEmbedding = [0.1, 0.2, 0.3];
        $limit = 2;

        $sameResults = [
            [
                'id' => 'chunk-1',
                'chunk_text' => 'Одинаковый результат',
                'similarity' => 0.9,
                'bm25_score' => 0.8,
                'rank' => 1,
                'embedding' => '[0.1,0.2,0.3]'
            ]
        ];

        $this->connection->shouldReceive('fetchAllAssociative')
            ->twice()
            ->andReturn($sameResults, $sameResults);

        $results = $this->repository->searchHybrid($queryText, $queryEmbedding, $limit);

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        
        // Проверяем, что результат содержит hybrid_score
        $this->assertArrayHasKey('hybrid_score', $results[0]);
    }

    /**
     * Тест для покрытия приватного метода prepareTsQuery - сложные запросы
     */
    public function testPrepareTsQueryWithComplexQueries(): void
    {
        $queryText = 'complex query with multiple words and special characters!@#$%';
        $limit = 5;

        $keywordResults = [
            ['id' => '1', 'title' => 'Doc 1', 'content' => 'Content 1', 'rank' => 1, 'embedding' => '[0.1,0.2,0.3]'],
        ];

        $this->connection->shouldReceive('fetchAllAssociative')
            ->once()
            ->andReturn($keywordResults);

        $results = $this->repository->searchByKeywords($queryText, $limit);

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }

    /**
     * Тест для покрытия приватного метода prepareTsQuery - пустые запросы
     */
    public function testPrepareTsQueryWithEmptyQuery(): void
    {
        $queryText = '';
        $limit = 5;

        $keywordResults = [];

        $this->connection->shouldReceive('fetchAllAssociative')
            ->once()
            ->andReturn($keywordResults);

        $results = $this->repository->searchByKeywords($queryText, $limit);

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * Тест для покрытия приватного метода prepareTsQuery - запросы с русскими символами
     */
    public function testPrepareTsQueryWithRussianText(): void
    {
        $queryText = 'тестовый запрос с русскими символами';
        $limit = 5;

        $keywordResults = [
            ['id' => '1', 'title' => 'Документ 1', 'content' => 'Содержимое 1', 'rank' => 1, 'embedding' => '[0.1,0.2,0.3]'],
        ];

        $this->connection->shouldReceive('fetchAllAssociative')
            ->once()
            ->andReturn($keywordResults);

        $results = $this->repository->searchByKeywords($queryText, $limit);

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }

    /**
     * Тест для покрытия приватного метода prepareTsQuery - запросы с цифрами
     */
    public function testPrepareTsQueryWithNumbers(): void
    {
        $queryText = 'query with 123 numbers and 456 more';
        $limit = 5;

        $keywordResults = [
            ['id' => '1', 'title' => 'Doc 1', 'content' => 'Content 1', 'rank' => 1, 'embedding' => '[0.1,0.2,0.3]'],
        ];

        $this->connection->shouldReceive('fetchAllAssociative')
            ->once()
            ->andReturn($keywordResults);

        $results = $this->repository->searchByKeywords($queryText, $limit);

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }
}