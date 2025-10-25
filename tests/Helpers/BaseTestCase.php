<?php

declare(strict_types=1);

namespace RagSystem\Tests\Helpers;

use PHPUnit\Framework\TestCase;
use Mockery;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Connection;
use RagSystem\Tests\Helpers\MockDatabaseConnection;
use RagSystem\Infrastructure\Service\LlamaCppAdapter;
use RagSystem\Domain\Service\QueueServiceInterface;
use RagSystem\Domain\Repository\DocumentRepositoryInterface;
use RagSystem\Domain\Repository\QueryRepositoryInterface;
use RagSystem\Domain\Repository\UserRepositoryInterface;

abstract class BaseTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->initializeTestEnvironment();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        
        parent::tearDown();
    }

    /**
     * Инициализация тестового окружения
     */
    protected function initializeTestEnvironment(): void
    {
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['APP_DEBUG'] = 'true';
    }

    /**
     * Создание мок-логгера
     */
    protected function createMockLogger(): LoggerInterface
    {
        $logger = Mockery::mock(LoggerInterface::class);

        $logger->shouldReceive('debug')->andReturnNull();
        $logger->shouldReceive('info')->andReturnNull();
        $logger->shouldReceive('warning')->andReturnNull();
        $logger->shouldReceive('error')->andReturnNull();
        $logger->shouldReceive('critical')->andReturnNull();
        
        return $logger;
    }

    /**
     * Создание мок-соединения с базой данных
     */
    protected function createMockDatabaseConnection(): MockDatabaseConnection
    {
        return new MockDatabaseConnection();
    }

    /**
     * Создание мок-адаптера LlamaCpp
     */
    protected function createMockLlamaCppAdapter(): LlamaCppAdapter
    {
        $adapter = Mockery::mock(LlamaCppAdapter::class);

        $adapter->shouldReceive('ensureModelLoaded')->andReturn(true);
        $adapter->shouldReceive('generateEmbedding')->andReturn([0.1, 0.2, 0.3]);
        $adapter->shouldReceive('generateCompletion')->andReturn('Тестовый ответ');
        $adapter->shouldReceive('processRAGPrompt')->andReturnUsing(function ($prompt) {
            return $prompt;
        });
        
        return $adapter;
    }

    /**
     * Создание мок-сервиса очередей
     */
    protected function createMockQueueService(): QueueServiceInterface
    {
        $queueService = Mockery::mock(QueueServiceInterface::class);

        $queueService->shouldReceive('publish')->andReturnNull();
        $queueService->shouldReceive('consume')->andReturnNull();
        $queueService->shouldReceive('getQueueStatus')->andReturn(['status' => 'healthy']);
        $queueService->shouldReceive('isConnected')->andReturn(true);
        
        return $queueService;
    }

    /**
     * Создание мок-репозитория документов
     */
    protected function createMockDocumentRepository(): DocumentRepositoryInterface
    {
        $repository = Mockery::mock(DocumentRepositoryInterface::class);

        $repository->shouldReceive('save')->andReturnNull();
        $repository->shouldReceive('findById')->andReturn(null);
        $repository->shouldReceive('findAll')->andReturn([]);
        $repository->shouldReceive('delete')->andReturn(false);
        $repository->shouldReceive('saveChunk')->andReturnNull();
        $repository->shouldReceive('findChunksByDocumentId')->andReturn([]);
        $repository->shouldReceive('deleteChunksByDocumentId')->andReturnNull();
        $repository->shouldReceive('searchSimilarChunks')->andReturn([]);
        $repository->shouldReceive('searchByKeywords')->andReturn([]);
        $repository->shouldReceive('searchHybrid')->andReturn([]);

        return $repository;
    }

    /**
     * Создание мок-репозитория запросов
     */
    protected function createMockQueryRepository(): QueryRepositoryInterface
    {
        $repository = Mockery::mock(QueryRepositoryInterface::class);

        $repository->shouldReceive('save')->andReturnNull();
        $repository->shouldReceive('findById')->andReturn(null);
        $repository->shouldReceive('findAll')->andReturn([]);
        $repository->shouldReceive('findByQueryText')->andReturn([]);
        $repository->shouldReceive('findSimilarQueries')->andReturn([]);
        
        return $repository;
    }

    /**
     * Создание мок-репозитория пользователей
     */
    protected function createMockUserRepository(): UserRepositoryInterface
    {
        $repository = Mockery::mock(UserRepositoryInterface::class);

        $repository->shouldReceive('save')->andReturnNull();
        $repository->shouldReceive('findById')->andReturn(null);
        $repository->shouldReceive('findByEmail')->andReturn(null);
        $repository->shouldReceive('findByUsername')->andReturn(null);
        $repository->shouldReceive('findAll')->andReturn([]);
        $repository->shouldReceive('delete')->andReturnNull();
        
        return $repository;
    }

    /**
     * Проверка, что массив имеет ожидаемую структуру
     */
    protected function assertArrayStructure(array $expectedStructure, array $actualArray, string $message = ''): void
    {
        foreach ($expectedStructure as $key => $expectedType) {
            $this->assertArrayHasKey($key, $actualArray, $message ?: "Массив должен содержать ключ '{$key}'");
            
            if (is_string($expectedType)) {
                $this->assertIsString($actualArray[$key], $message ?: "Ключ '{$key}' должен быть строкой");
            } elseif (is_int($expectedType)) {
                $this->assertIsInt($actualArray[$key], $message ?: "Ключ '{$key}' должен быть целым числом");
            } elseif (is_array($expectedType)) {
                $this->assertIsArray($actualArray[$key], $message ?: "Ключ '{$key}' должен быть массивом");
                if (!empty($expectedType)) {
                    $this->assertArrayStructure($expectedType, $actualArray[$key], $message);
                }
            }
        }
    }

    /**
     * Проверка, что ответ имеет ожидаемую API структуру
     */
    protected function assertApiResponseStructure(array $response, string $message = ''): void
    {
        $this->assertArrayHasKey('success', $response, $message ?: 'Ответ должен содержать ключ success');
        $this->assertIsBool($response['success'], $message ?: 'Success должен быть булевым значением');
        
        if (isset($response['data'])) {
            $this->assertIsArray($response['data'], $message ?: 'Data должен быть массивом');
        }
        
        if (isset($response['message'])) {
            $this->assertIsString($response['message'], $message ?: 'Message должен быть строкой');
        }
        
        if (isset($response['errors'])) {
            $this->assertIsArray($response['errors'], $message ?: 'Errors должен быть массивом');
        }
    }

    /**
     * Получение пути к директории тестовых данных
     */
    protected function getTestDataPath(): string
    {
        return __DIR__ . '/Fixtures/test_documents';
    }

    /**
     * Загрузка тестовых данных из файла
     */
    protected function loadTestData(string $filename): string
    {
        $filePath = $this->getTestDataPath() . '/' . $filename;
        
        if (!file_exists($filePath)) {
            $this->fail("Файл тестовых данных не найден: {$filePath}");
        }
        
        return file_get_contents($filePath);
    }
}
