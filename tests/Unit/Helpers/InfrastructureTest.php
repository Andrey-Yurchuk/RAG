<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Helpers;

use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Tests\Helpers\TestDataFactory;
use RagSystem\Tests\Helpers\MockLlamaCppAdapter;
use RagSystem\Tests\Helpers\MockRabbitMQService;
use RagSystem\Tests\Helpers\MockDatabaseConnection;

/**
 * Тест инфраструктуры
 * 
 * @covers \RagSystem\Tests\Helpers\BaseTestCase
 * @covers \RagSystem\Tests\Helpers\TestDataFactory
 * @covers \RagSystem\Tests\Helpers\MockRabbitMQService
 * @covers \RagSystem\Tests\Helpers\MockLlamaCppAdapter
 * @covers \RagSystem\Tests\Helpers\MockDatabaseConnection
 */
class InfrastructureTest extends BaseTestCase
{
    /**
     * Тест создания экземпляра BaseTestCase
     */
    public function testBaseTestCaseCanBeInstantiated(): void
    {
        $this->assertInstanceOf(BaseTestCase::class, $this);
    }

    /**
     * Тест создания валидных данных TestDataFactory
     */
    public function testTestDataFactoryCreatesValidData(): void
    {
        $document = TestDataFactory::createDocument();
        
        $this->assertIsArray($document);
        $this->assertArrayHasKey('id', $document);
        $this->assertArrayHasKey('title', $document);
        $this->assertArrayHasKey('content', $document);
        $this->assertIsString($document['title']);
        $this->assertIsString($document['content']);
    }

    /**
     * Тест работы MockLlamaCppAdapter
     */
    public function testMockLlamaCppAdapterWorks(): void
    {
        $adapter = new MockLlamaCppAdapter();
        
        $this->assertTrue($adapter->ensureModelLoaded());
        
        $embedding = $adapter->generateEmbedding('test text');
        $this->assertIsArray($embedding);
        $this->assertCount(384, $embedding);
        
        $completion = $adapter->generateCompletion('test prompt');
        $this->assertIsString($completion);
        $this->assertNotEmpty($completion);
    }

    /**
     * Тест работы MockRabbitMQService
     */
    public function testMockRabbitMQServiceWorks(): void
    {
        $service = new MockRabbitMQService();
        
        $this->assertTrue($service->isConnected());
        
        $service->publish('test.queue', ['message' => 'test']);
        
        $messages = $service->getPublishedMessages('test.queue');
        $this->assertCount(1, $messages);
        $this->assertEquals('test', $messages[0]['message']);
    }

    /**
     * Тест работы MockDatabaseConnection
     */
    public function testMockDatabaseConnectionWorks(): void
    {
        $connection = new MockDatabaseConnection();
        
        $connection->setMockData('documents', [
            ['id' => '1', 'title' => 'Test Document']
        ]);
        
        $result = $connection->fetchAllAssociative('SELECT * FROM documents');
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Test Document', $result[0]['title']);
    }

    /**
     * Тест создания мок-сервисов через BaseTestCase
     */
    public function testMockServicesCanBeCreatedFromBaseTestCase(): void
    {
        $logger = $this->createMockLogger();
        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $logger);
        
        $connection = $this->createMockDatabaseConnection();
        $this->assertInstanceOf(\RagSystem\Tests\Helpers\MockDatabaseConnection::class, $connection);
        
        $adapter = $this->createMockLlamaCppAdapter();
        $this->assertInstanceOf(\RagSystem\Infrastructure\Service\LlamaCppAdapter::class, $adapter);
        
        $queueService = $this->createMockQueueService();
        $this->assertInstanceOf(\RagSystem\Domain\Service\QueueServiceInterface::class, $queueService);
    }

    /**
     * Тест создания множественных документов TestDataFactory
     */
    public function testTestDataFactoryCreatesMultipleDocuments(): void
    {
        $documents = TestDataFactory::createDocuments(3);
        
        $this->assertCount(3, $documents);
        
        foreach ($documents as $document) {
            $this->assertIsArray($document);
            $this->assertArrayHasKey('title', $document);
            $this->assertStringContainsString('Тестовый документ', $document['title']);
        }
    }

    /**
     * Тест создания содержимого файлов TestDataFactory
     */
    public function testTestDataFactoryCreatesFileContent(): void
    {
        $txtContent = TestDataFactory::createFileContent('txt');
        $this->assertIsString($txtContent);
        $this->assertNotEmpty($txtContent);
        
        $mdContent = TestDataFactory::createFileContent('md');
        $this->assertIsString($mdContent);
        $this->assertStringContainsString('#', $mdContent);
        
        $htmlContent = TestDataFactory::createFileContent('html');
        $this->assertIsString($htmlContent);
        $this->assertStringContainsString('<html>', $htmlContent);
    }

    /**
     * Тест создания эмбеддингов TestDataFactory
     */
    public function testTestDataFactoryCreatesEmbeddings(): void
    {
        $embedding = TestDataFactory::createEmbedding(384);
        
        $this->assertIsArray($embedding);
        $this->assertCount(384, $embedding);
        
        foreach ($embedding as $value) {
            $this->assertIsFloat($value);
            $this->assertGreaterThanOrEqual(-1, $value);
            $this->assertLessThanOrEqual(1, $value);
        }
    }

    /**
     * Тест поддержки русского языка
     */
    public function testRussianLanguageSupport(): void
    {
        $document = TestDataFactory::createDocument();
        $this->assertStringContainsString('Тестовый документ', $document['title']);
        $this->assertStringContainsString('содержимое', $document['content']);

        $query = TestDataFactory::createQuery();
        $this->assertStringContainsString('документ', $query['query_text']);
        $this->assertStringContainsString('тестировании', $query['response']);

        $txtContent = TestDataFactory::createFileContent('txt');
        $this->assertStringContainsString('текстовый файл', $txtContent);

        $adapter = new MockLlamaCppAdapter();
        $response = $adapter->generateCompletion('О чем этот документ?');
        $this->assertStringContainsString('мок-ответ', $response);
    }
}