<?php

declare(strict_types=1);

namespace RagSystem\UI\Http\Controller;

use Exception;
use RagSystem\Infrastructure\Http\Request;
use RagSystem\Infrastructure\Http\Response;
use RagSystem\Infrastructure\Service\LlamaCppAdapter;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class HealthController
{
    public function __construct(
        private Connection $connection,
        private LlamaCppAdapter $llamaCppAdapter,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Возвращает статус healthcheck системы и всех сервисов
     */
    public function check(Request $request): Response
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0.0',
            'services' => [
                'database' => $this->checkDatabase(),
                'llm' => $this->checkLLM(),
            ]
        ];

        return Response::json($health);
    }

    /**
     * Проверяет доступность базы данных
     */
    private function checkDatabase(): string
    {
        try {
            $this->connection->executeQuery('SELECT 1');
            return 'healthy';
        } catch (Exception $e) {
            $this->logger->error('Database health check failed', ['error' => $e->getMessage()]);
            return 'unhealthy';
        }
    }

    /**
     * Проверяет доступность языковой модели
     */
    private function checkLLM(): string
    {
        try {
            $isHealthy = $this->llamaCppAdapter->ensureModelLoaded();
            return $isHealthy ? 'healthy' : 'unhealthy';
        } catch (Exception $e) {
            $this->logger->error('LLM health check failed', ['error' => $e->getMessage()]);
            return 'unhealthy';
        }
    }
}
