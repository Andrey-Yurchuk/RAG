<?php

declare(strict_types=1);

namespace RagSystem\Infrastructure\DependencyInjection;

// Load custom DBAL types
require_once __DIR__ . '/../../../config/dbal-types.php';

use RagSystem\Domain\Repository\DocumentRepositoryInterface;
use RagSystem\Domain\Repository\QueryRepositoryInterface;
use RagSystem\Infrastructure\Database\PostgreSQLDocumentRepository;
use RagSystem\Infrastructure\Database\PostgreSQLQueryRepository;
use RagSystem\Infrastructure\Service\LlamaCppAdapter;
use RagSystem\Application\Service\DocumentService;
use RagSystem\Application\Service\QueryService;
use RagSystem\Application\Service\EmbeddingService;
use RagSystem\Application\Service\LLMService;
use RagSystem\Application\Service\TextProcessingService;
use RagSystem\UI\Http\Controller\DocumentController;
use RagSystem\UI\Http\Controller\QueryController;
use RagSystem\UI\Http\Controller\HealthController;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use GuzzleHttp\Client;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;

class ServiceProvider
{
    /**
     * Регистрирует все сервисы приложения в DI-контейнере
     */
    public static function register(Container $container): void
    {
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

        // Services
        $container->bind(TextProcessingService::class, function (Container $container) {
            $config = $container->get('config');
            return new TextProcessingService($config['services']);
        });

        $container->bind(EmbeddingService::class, function (Container $container) {
            return new EmbeddingService(
                $container->get(LlamaCppAdapter::class),
                $container->get(LoggerInterface::class),
                $container->get('config')['services']
            );
        });

        $container->bind(LLMService::class, function (Container $container) {
            return new LLMService(
                $container->get(LlamaCppAdapter::class),
                $container->get(LoggerInterface::class),
                $container->get('config')['services']
            );
        });

        $container->bind(DocumentService::class, function (Container $container) {
            return new DocumentService(
                $container->get(DocumentRepositoryInterface::class),
                $container->get(EmbeddingService::class),
                $container->get(TextProcessingService::class),
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
    }
}