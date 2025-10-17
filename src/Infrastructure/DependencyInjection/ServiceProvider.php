<?php

declare(strict_types=1);

namespace RagSystem\Infrastructure\DependencyInjection;

use Exception;
use RagSystem\Domain\Repository\DocumentRepositoryInterface;
use RagSystem\Domain\Repository\QueryRepositoryInterface;
use RagSystem\Domain\Repository\UserRepositoryInterface;
use RagSystem\Domain\Repository\UserSessionRepositoryInterface;
use RagSystem\Infrastructure\Database\PostgreSQLDocumentRepository;
use RagSystem\Infrastructure\Database\PostgreSQLQueryRepository;
use RagSystem\Infrastructure\Database\PostgreSQLUserRepository;
use RagSystem\Infrastructure\Database\PostgreSQLUserSessionRepository;
use RagSystem\Infrastructure\Service\LlamaCppAdapter;
use RagSystem\Application\Service\DocumentService;
use RagSystem\Application\Service\QueryService;
use RagSystem\Application\Service\EmbeddingService;
use RagSystem\Application\Service\LLMService;
use RagSystem\Application\Service\TextProcessingService;
use RagSystem\Application\Service\AuthService;
use RagSystem\Application\Service\AuthorizationService;
use RagSystem\Application\Service\TaskService;
use RagSystem\Application\Service\TaskStatusService;
use RagSystem\Infrastructure\Service\RabbitMQService;
use RagSystem\Domain\Service\QueueServiceInterface;
use RagSystem\UI\Http\Controller\DocumentController;
use RagSystem\UI\Http\Controller\QueryController;
use RagSystem\UI\Http\Controller\HealthController;
use RagSystem\UI\Http\Controller\AuthController;
use RagSystem\Infrastructure\Http\Middleware\AuthMiddleware;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use GuzzleHttp\Client;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;
use RuntimeException;

class ServiceProvider
{
    /**
     * Регистрирует все сервисы приложения в DI-контейнере
     */
    public static function register(Container $container): void
    {
        // Load custom DBAL types
        require_once __DIR__ . '/../../../config/dbal-types.php';

        // Configuration
        $container->bind('config', function () {
            return [
                'database' => require __DIR__ . '/../../../config/database.php',
                'cache' => require __DIR__ . '/../../../config/cache.php',
                'services' => require __DIR__ . '/../../../config/services.php',
            ];
        });

        // Logger
        $container->bind(LoggerInterface::class, function () {
            $logger = new Logger('rag-service');
            $logger->pushHandler(new StreamHandler(
                $_ENV['LOG_FILE'] ?? '/app/storage/logs/app.log',
                Logger::DEBUG
            ));
            return $logger;
        });

        // Database Connection
        $container->bind(Connection::class, function (Container $container) {
            $config = $container->get('config');
            $dbConfig = $config['database']['connections']['postgresql'];

            return DriverManager::getConnection([
                'driver' => 'pdo_pgsql',
                'host' => $dbConfig['host'],
                'port' => $dbConfig['port'],
                'dbname' => $dbConfig['database'],
                'user' => $dbConfig['username'],
                'password' => $dbConfig['password'],
                'charset' => $dbConfig['charset'],
                'options' => $dbConfig['options'],
            ]);
        });

        // HTTP Client
        $container->bind(Client::class, function () {
            return new Client([
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);
        });

        // Queue Services
        $container->bind(QueueServiceInterface::class, function (Container $container) {
            $rabbitMQHost = $_ENV['RABBITMQ_HOST'] ?? null;
            $rabbitMQPort = $_ENV['RABBITMQ_PORT'] ?? null;
            $rabbitMQUser = $_ENV['RABBITMQ_USER'] ?? null;
            $rabbitMQPassword = $_ENV['RABBITMQ_PASSWORD'] ?? null;
            
            if (!$rabbitMQHost || !$rabbitMQPort || !$rabbitMQUser || !$rabbitMQPassword) {
                throw new RuntimeException("RabbitMQ configuration is incomplete. Please check environment variables: RABBITMQ_HOST, RABBITMQ_PORT, RABBITMQ_USER, RABBITMQ_PASSWORD");
            }
            
            return new RabbitMQService(
                $rabbitMQHost,
                (int)$rabbitMQPort,
                $rabbitMQUser,
                $rabbitMQPassword,
                $container->get(LoggerInterface::class)
            );
        });

        $container->bind(TaskService::class, function (Container $container) {
            return new TaskService(
                $container->get(QueueServiceInterface::class),
                $container->get(LoggerInterface::class)
            );
        });

        $container->bind(TaskStatusService::class, function (Container $container) {
            $redisHost = $_ENV['REDIS_HOST'] ?? null;
            $redisPort = $_ENV['REDIS_PORT'] ?? null;
            $redisPassword = $_ENV['REDIS_PASSWORD'] ?? null;
            $redisPrefix = $_ENV['REDIS_TASK_CACHE_PREFIX'] ?? null;
            
            if (!$redisHost || !$redisPort || !$redisPassword || !$redisPrefix) {
                throw new Exception("Redis configuration is incomplete. Please check environment variables: REDIS_HOST, REDIS_PORT, REDIS_PASSWORD, REDIS_TASK_CACHE_PREFIX");
            }
            
            return new TaskStatusService(
                $redisHost,
                (int)$redisPort,
                $redisPassword,
                $redisPrefix,
                $container->get(LoggerInterface::class)
            );
        });

        // LLM Adapter
        $container->bind(LlamaCppAdapter::class, function (Container $container) {
            $config = $container->get('config');

            return new LlamaCppAdapter(
                null,
                null,
                $config['services']
            );
        });

        // Repositories
        $container->bind(DocumentRepositoryInterface::class, PostgreSQLDocumentRepository::class);
        $container->bind(QueryRepositoryInterface::class, PostgreSQLQueryRepository::class);
        $container->bind(UserRepositoryInterface::class, PostgreSQLUserRepository::class);
        $container->bind(UserSessionRepositoryInterface::class, PostgreSQLUserSessionRepository::class);

        // Services
        $container->bind(TextProcessingService::class, function (Container $container) {
            $config = $container->get('config');
            return new TextProcessingService($config['services']);
        });

        $container->bind(EmbeddingService::class, function (Container $container) {
            return new EmbeddingService(
                $container->get(LlamaCppAdapter::class),
                $container->get(LoggerInterface::class)
            );
        });

        $container->bind(LLMService::class, function (Container $container) {
            return new LLMService(
                $container->get(LlamaCppAdapter::class),
                $container->get(LoggerInterface::class)
            );
        });

        $container->bind(DocumentService::class, function (Container $container) {
            return new DocumentService(
                $container->get(DocumentRepositoryInterface::class),
                $container->get(EmbeddingService::class),
                $container->get(TextProcessingService::class),
                $container->get(TaskService::class),
                $container->get(TaskStatusService::class),
                $container->get(LoggerInterface::class)
            );
        });

        $container->bind(QueryService::class, function (Container $container) {
            return new QueryService(
                $container->get(QueryRepositoryInterface::class),
                $container->get(DocumentRepositoryInterface::class),
                $container->get(EmbeddingService::class),
                $container->get(LLMService::class),
                $container->get(LoggerInterface::class),
                $container->get('config')['services']
            );
        });

        // Auth Services
        $container->bind(AuthService::class, function (Container $container) {
            return new AuthService(
                $container->get(UserRepositoryInterface::class),
                $container->get(UserSessionRepositoryInterface::class),
                $container->get(LoggerInterface::class)
            );
        });

        $container->bind(AuthorizationService::class, function () {
            return new AuthorizationService();
        });

        // Auth Middleware
        $container->bind(AuthMiddleware::class, function (Container $container) {
            return new AuthMiddleware(
                $container->get(AuthService::class),
                $container->get(AuthorizationService::class)
            );
        });

        // Controllers
        $container->bind(DocumentController::class, function (Container $container) {
            return new DocumentController(
                $container->get(DocumentService::class),
                $container->get(TextProcessingService::class),
                $container->get(LoggerInterface::class)
            );
        });

        $container->bind(QueryController::class, function (Container $container) {
            return new QueryController(
                $container->get(QueryService::class),
                $container->get(LoggerInterface::class)
            );
        });

        $container->bind(HealthController::class, function (Container $container) {
            return new HealthController(
                $container->get(Connection::class),
                $container->get(LlamaCppAdapter::class),
                $container->get(LoggerInterface::class)
            );
        });

        $container->bind(AuthController::class, function (Container $container) {
            return new AuthController(
                $container->get(AuthService::class),
                $container->get(AuthorizationService::class)
            );
        });
    }
}
