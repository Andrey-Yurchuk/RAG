<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Application\Service;

use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Tests\Helpers\TestDataFactory;
use RagSystem\Application\Service\QueryService;
use RagSystem\Application\Service\EmbeddingService;
use RagSystem\Application\Service\LLMService;
use RagSystem\Domain\Model\Query;
use RagSystem\Domain\Repository\QueryRepositoryInterface;
use RagSystem\Domain\Repository\DocumentRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Exception;
use RuntimeException;
use Mockery;

/**
 * Тесты для QueryService
 * 
 * @covers \RagSystem\Application\Service\QueryService
 * @covers \RagSystem\Domain\Model\Query
 */
class QueryServiceTest extends BaseTestCase
{
    private QueryService $queryService;
    private QueryRepositoryInterface $queryRepository;
    private DocumentRepositoryInterface $documentRepository;
    private EmbeddingService $embeddingService;
    private LLMService $llmService;
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryRepository = Mockery::mock(QueryRepositoryInterface::class);
        $this->documentRepository = Mockery::mock(DocumentRepositoryInterface::class);
        $this->embeddingService = Mockery::mock(EmbeddingService::class);
        $this->llmService = Mockery::mock(LLMService::class);

        $this->config = TestDataFactory::createConfig();

        $this->queryService = new QueryService(
            $this->queryRepository,
            $this->documentRepository,
            $this->embeddingService,
            $this->llmService,
            $this->createMockLogger(),
            $this->config
        );
    }

    /**
     * Тест обработки запроса с успешным ответом
     */
    public function testProcessQueryWithSuccessfulResponse(): void
    {
        $queryText = 'О чем этот документ?';
        $queryEmbedding = [0.1, 0.2, 0.3, 0.4, 0.5];
        $relevantChunks = [
            ['chunk_text' => 'Документ о тестировании', 'similarity_score' => 0.9],
            ['chunk_text' => 'Система RAG для обработки', 'similarity_score' => 0.8],
        ];
        $expectedResponse = 'Этот документ о тестировании системы RAG для обработки документов.';

        $this->embeddingService->shouldReceive('generateEmbedding')
            ->with($queryText)
            ->andReturn($queryEmbedding);
        $this->documentRepository->shouldReceive('searchHybrid')
            ->with(
                $queryText,
                $queryEmbedding,
                $this->config['vector_search']['limit'],
                $this->config['vector_search']['similarity_threshold'],
                $this->config['hybrid_search']['vector_top_k'],
                $this->config['hybrid_search']['keyword_top_k']
            )
            ->andReturn($relevantChunks);
        $this->llmService->shouldReceive('generateRAGResponse')
            ->with($queryText, $relevantChunks)
            ->andReturn($expectedResponse);
        $this->queryRepository->shouldReceive('save')->once();

        $query = $this->queryService->processQuery($queryText);

        $this->assertInstanceOf(Query::class, $query);
        $this->assertEquals($queryText, $query->getQueryText());
        $this->assertEquals($queryEmbedding, $query->getQueryEmbedding());
        $this->assertEquals($expectedResponse, $query->getResponse());
        $this->assertTrue($query->hasEmbedding());
        $this->assertTrue($query->hasResponse());
    }

    /**
     * Тест обработки запроса без релевантных фрагментов
     */
    public function testProcessQueryWithNoRelevantChunks(): void
    {
        $queryText = 'Несуществующий вопрос';
        $queryEmbedding = [0.1, 0.2, 0.3, 0.4, 0.5];

        $this->embeddingService->shouldReceive('generateEmbedding')
            ->with($queryText)
            ->andReturn($queryEmbedding);
        $this->documentRepository->shouldReceive('searchHybrid')
            ->andReturn([]);
        $this->queryRepository->shouldReceive('save')->once();

        $query = $this->queryService->processQuery($queryText);

        $this->assertEquals($queryText, $query->getQueryText());
        $this->assertEquals('No information was found in the uploaded documents to answer your question', $query->getResponse());
    }

    /**
     * Тест обработки запроса с временем ответа
     */
    public function testProcessQueryWithResponseTime(): void
    {
        $queryText = 'Тестовый запрос';
        $responseTime = 1.5;
        $queryEmbedding = [0.1, 0.2, 0.3];

        $this->embeddingService->shouldReceive('generateEmbedding')
            ->with($queryText)
            ->andReturn($queryEmbedding);
        $this->documentRepository->shouldReceive('searchHybrid')
            ->andReturn([]);
        $this->queryRepository->shouldReceive('save')->once();

        $query = $this->queryService->processQuery($queryText, $responseTime);

        $this->assertEquals($responseTime, $query->getResponseTime());
    }

    /**
     * Тест обработки запроса с ошибкой эмбеддинга
     */
    public function testProcessQueryWithEmbeddingError(): void
    {
        $queryText = 'Тестовый запрос';

        $this->embeddingService->shouldReceive('generateEmbedding')
            ->with($queryText)
            ->andThrow(new RuntimeException('Embedding service error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Embedding service error');

        $this->queryService->processQuery($queryText);
    }

    /**
     * Тест обработки запроса с ошибкой поиска
     */
    public function testProcessQueryWithSearchError(): void
    {
        $queryText = 'Тестовый запрос';
        $queryEmbedding = [0.1, 0.2, 0.3];

        $this->embeddingService->shouldReceive('generateEmbedding')
            ->with($queryText)
            ->andReturn($queryEmbedding);
        $this->documentRepository->shouldReceive('searchHybrid')
            ->andThrow(new RuntimeException('Search service error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Search service error');

        $this->queryService->processQuery($queryText);
    }

    /**
     * Тест обработки запроса с ошибкой LLM
     */
    public function testProcessQueryWithLLMError(): void
    {
        $queryText = 'Тестовый запрос';
        $queryEmbedding = [0.1, 0.2, 0.3];
        $relevantChunks = [['chunk_text' => 'Релевантный фрагмент']];

        $this->embeddingService->shouldReceive('generateEmbedding')
            ->with($queryText)
            ->andReturn($queryEmbedding);
        $this->documentRepository->shouldReceive('searchHybrid')
            ->andReturn($relevantChunks);
        $this->llmService->shouldReceive('generateRAGResponse')
            ->andThrow(new RuntimeException('LLM service error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('LLM service error');

        $this->queryService->processQuery($queryText);
    }

    /**
     * Тест поиска документов
     */
    public function testSearchDocuments(): void
    {
        $queryText = 'тестовый запрос';
        $queryEmbedding = [0.1, 0.2, 0.3];
        $expectedChunks = [
            ['chunk_text' => 'Релевантный фрагмент 1', 'similarity' => 0.9],
            ['chunk_text' => 'Релевантный фрагмент 2', 'similarity' => 0.8]
        ];

        $this->embeddingService->shouldReceive('generateEmbedding')
            ->with($queryText)
            ->andReturn($queryEmbedding);
        $this->documentRepository->shouldReceive('searchSimilarChunks')
            ->with($queryEmbedding, 10, 0.8)
            ->andReturn($expectedChunks);

        $result = $this->queryService->searchDocuments($queryText, 10, 0.8);

        $this->assertEquals($expectedChunks, $result);
    }

    /**
     * Тест поиска документов с кастомными параметрами
     */
    public function testSearchDocumentsWithCustomParams(): void
    {
        $queryText = 'поиск документов';
        $queryEmbedding = [0.1, 0.2, 0.3];
        $expectedChunks = [
            ['chunk_text' => 'Найденный фрагмент', 'similarity' => 0.95]
        ];

        $this->embeddingService->shouldReceive('generateEmbedding')
            ->with($queryText)
            ->andReturn($queryEmbedding);
        $this->documentRepository->shouldReceive('searchSimilarChunks')
            ->with($queryEmbedding, 5, 0.9)
            ->andReturn($expectedChunks);

        $result = $this->queryService->searchDocuments($queryText, 5, 0.9);

        $this->assertEquals($expectedChunks, $result);
    }

    /**
     * Тест получения истории запросов
     */
    public function testGetQueryHistory(): void
    {
        $expectedQueries = [
            new Query('Запрос 1'),
            new Query('Запрос 2')
        ];

        $this->queryRepository->shouldReceive('findAll')
            ->with(10, 0)
            ->andReturn($expectedQueries);

        $result = $this->queryService->getQueryHistory(10, 0);

        $this->assertEquals($expectedQueries, $result);
    }

    /**
     * Тест получения истории запросов с пагинацией
     */
    public function testGetQueryHistoryWithPagination(): void
    {
        $expectedQueries = [
            new Query('Запрос 1'),
            new Query('Запрос 2')
        ];

        $this->queryRepository->shouldReceive('findAll')
            ->with(5, 10)
            ->andReturn($expectedQueries);

        $result = $this->queryService->getQueryHistory(5, 10);

        $this->assertEquals($expectedQueries, $result);
    }

    /**
     * Тест поиска похожих запросов
     */
    public function testFindSimilarQueries(): void
    {
        $queryText = 'похожий запрос';
        $queryEmbedding = [0.1, 0.2, 0.3];
        $expectedQueries = [
            ['query_text' => 'Похожий запрос 1', 'similarity' => 0.9],
            ['query_text' => 'Похожий запрос 2', 'similarity' => 0.8]
        ];

        $this->embeddingService->shouldReceive('generateEmbedding')
            ->with($queryText)
            ->andReturn($queryEmbedding);
        $this->queryRepository->shouldReceive('findSimilarQueries')
            ->with($queryEmbedding, 5)
            ->andReturn($expectedQueries);

        $result = $this->queryService->findSimilarQueries($queryText, 5);

        $this->assertEquals($expectedQueries, $result);
    }

    /**
     * Тест поиска похожих запросов с кастомным лимитом
     */
    public function testFindSimilarQueriesWithCustomLimit(): void
    {
        $queryText = 'запрос для поиска';
        $queryEmbedding = [0.1, 0.2, 0.3];
        $expectedQueries = [
            ['query_text' => 'Похожий запрос', 'similarity' => 0.95]
        ];

        $this->embeddingService->shouldReceive('generateEmbedding')
            ->with($queryText)
            ->andReturn($queryEmbedding);
        $this->queryRepository->shouldReceive('findSimilarQueries')
            ->with($queryEmbedding, 3)
            ->andReturn($expectedQueries);

        $result = $this->queryService->findSimilarQueries($queryText, 3);

        $this->assertEquals($expectedQueries, $result);
    }

    /**
     * Тест обновления времени ответа
     */
    public function testUpdateResponseTime(): void
    {
        $queryId = '123e4567-e89b-12d3-a456-426614174000';
        $responseTime = 1.5;
        $expectedQuery = new Query('тестовый запрос');
        $expectedQuery->setResponseTime($responseTime);

        $this->queryRepository->shouldReceive('findById')
            ->with($queryId)
            ->andReturn($expectedQuery);
        $this->queryRepository->shouldReceive('save')
            ->with($expectedQuery)
            ->once();

        $result = $this->queryService->updateResponseTime($queryId, $responseTime);

        $this->assertEquals($expectedQuery, $result);
        $this->assertEquals($responseTime, $result->getResponseTime());
    }

    /**
     * Тест обновления времени ответа для несуществующего запроса
     */
    public function testUpdateResponseTimeForNonExistentQuery(): void
    {
        $queryId = '123e4567-e89b-12d3-a456-426614174000';
        $responseTime = 1.5;

        $this->queryRepository->shouldReceive('findById')
            ->with($queryId)
            ->andReturn(null);

        $result = $this->queryService->updateResponseTime($queryId, $responseTime);

        $this->assertNull($result);
    }

    /**
     * Тест обработки запроса с русским текстом
     */
    public function testProcessQueryWithRussianText(): void
    {
        $queryText = 'О чем этот документ? Расскажи подробно на русском языке.';
        $queryEmbedding = [0.1, 0.2, 0.3];
        $expectedResponse = 'Этот документ содержит информацию о тестировании системы RAG.';

        $this->embeddingService->shouldReceive('generateEmbedding')
            ->with($queryText)
            ->andReturn($queryEmbedding);
        $this->documentRepository->shouldReceive('searchHybrid')
            ->andReturn([]);
        $this->queryRepository->shouldReceive('save')->once();

        $query = $this->queryService->processQuery($queryText);

        $this->assertEquals($queryText, $query->getQueryText());
        $this->assertEquals('No information was found in the uploaded documents to answer your question', $query->getResponse());
    }

    /**
     * Тест обработки запроса с пустым текстом
     */
    public function testProcessQueryWithEmptyText(): void
    {
        $queryText = '';
        $queryEmbedding = [0.1, 0.2, 0.3];

        $this->embeddingService->shouldReceive('generateEmbedding')
            ->with($queryText)
            ->andReturn($queryEmbedding);
        $this->documentRepository->shouldReceive('searchHybrid')
            ->andReturn([]);
        $this->queryRepository->shouldReceive('save')->once();

        $query = $this->queryService->processQuery($queryText);

        $this->assertEquals($queryText, $query->getQueryText());
        $this->assertEquals('No information was found in the uploaded documents to answer your question', $query->getResponse());
    }

    /**
     * Тест обновления времени ответа с ошибкой
     */
    public function testUpdateResponseTimeWithError(): void
    {
        $queryId = '123e4567-e89b-12d3-a456-426614174000';
        $responseTime = 1.5;

        $this->queryRepository->shouldReceive('findById')
            ->with(Mockery::any())
            ->andThrow(new Exception('Database error'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->queryService->updateResponseTime($queryId, $responseTime);
    }

    /**
     * Тест поиска похожих запросов с ошибкой
     */
    public function testFindSimilarQueriesWithError(): void
    {
        $queryText = 'запрос с ошибкой';
        
        $this->embeddingService->shouldReceive('generateEmbedding')
            ->with($queryText)
            ->andThrow(new Exception('Embedding service error'));

        $result = $this->queryService->findSimilarQueries($queryText);

        $this->assertEquals([], $result);
    }

    /**
     * Тест поиска документов с ошибкой
     */
    public function testSearchDocumentsWithError(): void
    {
        $queryText = 'запрос с ошибкой';
        
        $this->embeddingService->shouldReceive('generateEmbedding')
            ->with($queryText)
            ->andThrow(new Exception('Embedding service error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to search documents: Embedding service error');

        $this->queryService->searchDocuments($queryText);
    }
}