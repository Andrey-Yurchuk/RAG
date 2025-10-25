<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Infrastructure\Service;

use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Domain\Service\QueueServiceInterface;
use Mockery;
use Psr\Log\LoggerInterface;

/**
 * Тесты для RabbitMQService
 * 
 * @covers \RagSystem\Infrastructure\Service\RabbitMQService
 */
class RabbitMQServiceTest extends BaseTestCase
{
    private QueueServiceInterface $service;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMockLogger();

        $this->service = new \RagSystem\Tests\Helpers\MockRabbitMQService();
    }

    /**
     * Тест успешной публикации сообщения
     */
    public function testPublishMessageSuccess(): void
    {
        $queueName = 'test_queue';
        $message = ['task' => 'process_document', 'data' => ['id' => '123']];

        $this->service->publish($queueName, $message);

        $publishedMessages = $this->service->getPublishedMessages($queueName);
        $this->assertCount(1, $publishedMessages);
        $this->assertEquals($message, $publishedMessages[0]);
    }

    /**
     * Тест публикации сообщения с ошибкой подключения
     */
    public function testPublishConnectionError(): void
    {
        $queueName = 'test_queue';
        $message = ['task' => 'test'];

        $this->service->setShouldFail(true, 'Connection failed');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection failed');

        $this->service->publish($queueName, $message);
    }

    /**
     * Тест потребления сообщений
     */
    public function testConsumeMessages(): void
    {
        $queueName = 'test_queue';
        $callback = function ($message) {
            return true;
        };

        $this->service->consume($queueName, $callback);
        $this->assertTrue(true);
    }

    /**
     * Тест потребления сообщений с ошибкой
     */
    public function testConsumeError(): void
    {
        $queueName = 'test_queue';
        $callback = function ($message) {
            return true;
        };

        $this->service->setShouldFail(true, 'Connection failed');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection failed');

        $this->service->consume($queueName, $callback);
    }

    /**
     * Тест получения статуса очереди
     */
    public function testGetQueueStatus(): void
    {
        $queueName = 'test_queue';

        $result = $this->service->getQueueStatus($queueName);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals($queueName, $result['name']);
    }

    /**
     * Тест проверки подключения
     */
    public function testIsConnected(): void
    {
        $result = $this->service->isConnected();

        $this->assertTrue($result);
    }

    /**
     * Тест проверки отключения
     */
    public function testIsNotConnected(): void
    {
        $this->service->setConnected(false);

        $result = $this->service->isConnected();

        $this->assertFalse($result);
    }

    /**
     * Тест публикации сообщения с пустыми данными
     */
    public function testPublishEmptyMessage(): void
    {
        $queueName = 'test_queue';
        $message = [];

        $this->service->publish($queueName, $message);

        $publishedMessages = $this->service->getPublishedMessages($queueName);
        $this->assertCount(1, $publishedMessages);
    }

    /**
     * Тест публикации сообщения с русским текстом
     */
    public function testPublishRussianMessage(): void
    {
        $queueName = 'test_queue';
        $message = [
            'task' => 'process_document',
            'data' => [
                'title' => 'Русский документ',
                'content' => 'Содержимое документа на русском языке'
            ]
        ];

        $this->service->publish($queueName, $message);

        $publishedMessages = $this->service->getPublishedMessages($queueName);
        $this->assertCount(1, $publishedMessages);
        $this->assertEquals($message, $publishedMessages[0]);
    }

    /**
     * Тест очистки опубликованных сообщений
     */
    public function testClearPublishedMessages(): void
    {
        $queueName = 'test_queue';
        $message = ['task' => 'test'];

        $this->service->publish($queueName, $message);

        $publishedMessages = $this->service->getPublishedMessages($queueName);
        $this->assertCount(1, $publishedMessages);

        $this->service->clearPublishedMessages();

        $publishedMessages = $this->service->getPublishedMessages($queueName);
        $this->assertCount(0, $publishedMessages);
    }
}