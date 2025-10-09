<?php

declare(strict_types=1);

namespace RagSystem\UI\Http\Controller;

use Exception;
use RagSystem\Infrastructure\Http\Request;
use RagSystem\Infrastructure\Http\Response;
use RagSystem\Application\Service\QueryService;
use Psr\Log\LoggerInterface;

class QueryController
{
    public function __construct(
        private QueryService $queryService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Обрабатывает пользовательский запрос и возвращает ответ от LLM
     */
    public function query(Request $request): Response
    {
        try {
            $data = $request->getBody();

            if (!isset($data['query'])) {
                return Response::badRequest('Query parameter is required');
            }

            $queryText = $data['query'];

            if (empty(trim($queryText))) {
                return Response::badRequest('Query cannot be empty');
            }

            $query = $this->queryService->processQuery($queryText);

            return Response::json([
                'success' => true,
                'data' => [
                    'query_id' => $query->getId()->toString(),
                    'query_text' => $query->getQueryText(),
                    'response' => $query->getResponse(),
                    'created_at' => $query->getCreatedAt()->format('Y-m-d H:i:s')
                ],
                'message' => 'Query processed successfully'
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to process query', [
                'query' => $data['query'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return Response::internalServerError('Failed to process query');
        }
    }

    /**
     * Ищет документы по текстовому запросу с использованием векторного поиска
     */
    public function search(Request $request): Response
    {
        try {
            $data = $request->getBody();

            if (!isset($data['query'])) {
                return Response::badRequest('Query parameter is required');
            }

            $queryText = $data['query'];
            $limit = (int) ($data['limit'] ?? 10);
            $threshold = (float) ($data['threshold'] ?? 0.8);

            if (empty(trim($queryText))) {
                return Response::badRequest('Query cannot be empty');
            }

            $results = $this->queryService->searchDocuments($queryText, $limit, $threshold);

            return Response::json([
                'success' => true,
                'data' => [
                    'query' => $queryText,
                    'results' => $results,
                    'count' => count($results)
                ],
                'message' => 'Search completed successfully'
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to search documents', [
                'query' => $data['query'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return Response::internalServerError('Failed to search documents');
        }
    }

    /**
     * Возвращает историю запросов с пагинацией
     */
    public function history(Request $request): Response
    {
        try {
            $limit = (int) ($request->getQueryParam('limit') ?? 10);
            $offset = (int) ($request->getQueryParam('offset') ?? 0);

            $queries = $this->queryService->getQueryHistory($limit, $offset);

            $data = array_map(function ($query) {
                return [
                    'query_id' => $query->getId()->toString(),
                    'query_text' => $query->getQueryText(),
                    'response' => $query->getResponse(),
                    'created_at' => $query->getCreatedAt()->format('Y-m-d H:i:s')
                ];
            }, $queries);

            return Response::json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'count' => count($data)
                ]
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to fetch query history', ['error' => $e->getMessage()]);
            return Response::internalServerError('Failed to fetch query history');
        }
    }

    /**
     * Находит похожие запросы на основе векторного сходства
     */
    public function similar(Request $request): Response
    {
        try {
            $data = $request->getBody();

            if (!isset($data['query'])) {
                return Response::badRequest('Query parameter is required');
            }

            $queryText = $data['query'];
            $limit = (int) ($data['limit'] ?? 5);

            if (empty(trim($queryText))) {
                return Response::badRequest('Query cannot be empty');
            }

            $similarQueries = $this->queryService->findSimilarQueries($queryText, $limit);

            return Response::json([
                'success' => true,
                'data' => [
                    'query' => $queryText,
                    'similar_queries' => $similarQueries,
                    'count' => count($similarQueries)
                ],
                'message' => 'Similar queries found'
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to find similar queries', [
                'query' => $data['query'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return Response::internalServerError('Failed to find similar queries');
        }
    }
}