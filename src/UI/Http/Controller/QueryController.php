<?php

declare(strict_types=1);

namespace RagSystem\UI\Http\Controller;

use Exception;
use RagSystem\Application\DTO\Query\QueryRequestDTO;
use RagSystem\Application\DTO\Response\ApiResponseDTO;
use RagSystem\Application\Factory\ApiResponseFactory;
use RagSystem\Application\Validation\Query\QueryRequestValidator;
use RagSystem\Infrastructure\Http\Request;
use RagSystem\Infrastructure\Http\Response;
use RagSystem\Application\Service\QueryService;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;

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
            $dto = QueryRequestDTO::fromArray($request->getBody());
            $validator = new QueryRequestValidator();
            $validationResult = $validator->validate($dto);

            if (!$validationResult->isValid()) {
                $responseDto = ApiResponseFactory::error('Validation failed', $validationResult->getErrors(), 400);
                return Response::json($responseDto->toArray(), 400);
            }

            $query = $this->queryService->processQuery($dto->query, $dto->responseTime);

            $responseDto = ApiResponseFactory::success('Query processed successfully', [
                'query_id' => $query->getId()->toString(),
                'query_text' => $query->getQueryText(),
                'response' => $query->getResponse(),
                'response_time' => $query->getResponseTime(),
                'created_at' => $query->getCreatedAt()->format('Y-m-d H:i:s')
            ]);

            return Response::json($responseDto->toArray());
        } catch (InvalidArgumentException $e) {
            $responseDto = ApiResponseFactory::error('Invalid request data', ['request' => $e->getMessage()], 400);
            return Response::json($responseDto->toArray(), 400);
        } catch (Exception $e) {
            $this->logger->error('Failed to process query', [
                'query' => $request->getBody()['query'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            $responseDto = ApiResponseFactory::error(
                'Failed to process query',
                ['server' => 'Internal server error'],
                500
            );
            return Response::json($responseDto->toArray(), 500);
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
            $totalCount = $this->queryService->getTotalQueriesCount();

            $data = array_map(function ($query) {
                return [
                    'query_id' => $query->getId()->toString(),
                    'query_text' => $query->getQueryText(),
                    'response' => $query->getResponse(),
                    'response_time' => $query->getResponseTime(),
                    'created_at' => $query->getCreatedAt()->format('Y-m-d H:i:s')
                ];
            }, $queries);

            return Response::json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'count' => count($data),
                    'total' => $totalCount,
                    'has_more' => ($offset + count($data)) < $totalCount
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

    /**
     * Обновляет время ответа для существующего запроса
     */
    public function updateResponseTime(Request $request): Response
    {
        try {
            $queryId = $request->getPathParam('id');
            $data = $request->getBody();

            if (!isset($data['response_time'])) {
                return Response::badRequest('Response time parameter is required');
            }

            $responseTime = (float) $data['response_time'];

            $query = $this->queryService->updateResponseTime($queryId, $responseTime);

            if (!$query) {
                return Response::notFound('Query not found');
            }

            return Response::json([
                'success' => true,
                'data' => [
                    'query_id' => $query->getId()->toString(),
                    'response_time' => $query->getResponseTime()
                ],
                'message' => 'Response time updated successfully'
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to update response time', [
                'query_id' => $request->getPathParam('id'),
                'error' => $e->getMessage()
            ]);
            return Response::internalServerError('Failed to update response time');
        }
    }
}
