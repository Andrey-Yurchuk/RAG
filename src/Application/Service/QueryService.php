<?php

declare(strict_types=1);

namespace RagSystem\Application\Service;

use Exception;
use RagSystem\Domain\Model\Query;
use RagSystem\Domain\Repository\QueryRepositoryInterface;
use RagSystem\Domain\Repository\DocumentRepositoryInterface;
use RagSystem\Application\Service\EmbeddingService;
use RagSystem\Application\Service\LLMService;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Ramsey\Uuid\Uuid;

class QueryService
{
    public function __construct(
        private QueryRepositoryInterface $queryRepository,
        private DocumentRepositoryInterface $documentRepository,
        private EmbeddingService $embeddingService,
        private LLMService $llmService,
        private LoggerInterface $logger,
        private array $config
    ) {
    }

    /**
     * Обрабатывает запрос и генерирует RAG ответ
     */
    public function processQuery(string $queryText, ?float $responseTime = null): Query
    {
        $this->logger->info('Processing query', ['query' => $queryText]);

        $query = new Query($queryText);

        if ($responseTime !== null) {
            $query->setResponseTime($responseTime);
        }

        try {
            // Генерация эмбеддинга для запроса
            $queryEmbedding = $this->embeddingService->generateEmbedding($queryText);
            $query->setQueryEmbedding($queryEmbedding);

            // Гибридный поиск релевантных чанков (Vector + BM25 + RRF)
            $relevantChunks = $this->documentRepository->searchHybrid(
                $queryText,
                $queryEmbedding,
                $this->config['vector_search']['limit'],
                $this->config['vector_search']['similarity_threshold'],
                $this->config['hybrid_search']['vector_top_k'] ?? 20,
                $this->config['hybrid_search']['keyword_top_k'] ?? 20
            );

            // Генерация ответа с помощью LLM
            if (!empty($relevantChunks)) {
                $this->logger->info('Generating RAG response with chunks', [
                    'chunks_count' => count($relevantChunks),
                    'query' => $queryText
                ]);

                $response = $this->llmService->generateRAGResponse($queryText, $relevantChunks);
                $query->setResponse($response);
            } else {
                $this->logger->warning('No relevant chunks found for query', [
                    'query' => $queryText,
                    'threshold' => $this->config['vector_search']['similarity_threshold']
                ]);
                $query->setResponse('No information was found in the uploaded documents to answer your question');
            }

            $this->queryRepository->save($query);

            $this->logger->info('Query processed successfully', [
                'query_id' => $query->getId()->toString(),
                'has_response' => $query->hasResponse()
            ]);

            return $query;
        } catch (Exception $e) {
            $this->logger->error('Failed to process query', [
                'query' => $queryText,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new RuntimeException('Failed to process query: ' . $e->getMessage());
        }
    }

    /**
     * Поиск документов по векторному сходству без генерации ответа
     *
     * @param string $queryText Поисковый запрос
     * @param int $limit Максимальное количество результатов
     * @param float $threshold Порог схожести от 0.0 до 1.0
     * @return array Массив найденных фрагментов документов
     */
    public function searchDocuments(string $queryText, int $limit = 10, float $threshold = 0.8): array
    {
        try {
            $queryEmbedding = $this->embeddingService->generateEmbedding($queryText);

            return $this->documentRepository->searchSimilarChunks(
                $queryEmbedding,
                $limit,
                $threshold
            );
        } catch (Exception $e) {
            $this->logger->error('Failed to search documents', [
                'query' => $queryText,
                'error' => $e->getMessage()
            ]);

            throw new RuntimeException('Failed to search documents: ' . $e->getMessage());
        }
    }

    /**
     * Получает историю запросов с пагинацией
     *
     * @param int $limit Лимит запросов (максимальное количество для возврата)
     * @param int $offset Смещение (количество запросов для пропуска)
     * @return array Массив запросов
     */
    public function getQueryHistory(int $limit = 10, int $offset = 0): array
    {
        return $this->queryRepository->findAll($limit, $offset);
    }

    /**
     * Получает общее количество запросов в бд
     */
    public function getTotalQueriesCount(): int
    {
        return $this->queryRepository->count();
    }

    /**
     * Поиск похожих запросов в истории
     *
     * @param string $queryText Текстовый запрос для поиска
     * @param int $limit Максимальное количество похожих запросов
     * @return array Массив похожих запросов
     */
    public function findSimilarQueries(string $queryText, int $limit = 5): array
    {
        try {
            $queryEmbedding = $this->embeddingService->generateEmbedding($queryText);

            return $this->queryRepository->findSimilarQueries($queryEmbedding, $limit);
        } catch (Exception $e) {
            $this->logger->error('Failed to find similar queries', [
                'query' => $queryText,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Обновляет время ответа для существующего запроса
     */
    public function updateResponseTime(string $queryId, float $responseTime): ?Query
    {
        try {
            $query = $this->queryRepository->findById(Uuid::fromString($queryId));

            if (!$query) {
                return null;
            }

            $query->setResponseTime($responseTime);
            $this->queryRepository->save($query);

            $this->logger->info('Response time updated', [
                'query_id' => $queryId,
                'response_time' => $responseTime
            ]);

            return $query;
        } catch (Exception $e) {
            $this->logger->error('Failed to update response time', [
                'query_id' => $queryId,
                'response_time' => $responseTime,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
