<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Application\Service;

use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Application\Service\TaskService;
use RagSystem\Domain\Service\QueueServiceInterface;
use Mockery;

/**
 * @covers \RagSystem\Application\Service\TaskService
 */
class TaskServiceTest extends BaseTestCase
{
    private TaskService $taskService;
    private QueueServiceInterface $queueService;
    private \Psr\Log\LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->queueService = Mockery::mock(QueueServiceInterface::class);
        $this->logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        
        $this->taskService = new TaskService($this->queueService, $this->logger);
    }

    /**
     * Тест публикации задачи обработки документа
     */
    public function testPublishDocumentProcessingTask(): void
    {
        $documentId = '123e4567-e89b-12d3-a456-426614174000';
        $filePath = '/path/to/document.pdf';
        $fileType = 'application/pdf';
        $content = 'Test document content';

        $this->queueService->shouldReceive('publish')
            ->with('document.processing', Mockery::type('array'))
            ->once();

        $this->logger->shouldReceive('info')
            ->with('Document processing task published', [
                'document_id' => $documentId,
                'file_type' => $fileType
            ])
            ->once();

        $this->taskService->publishDocumentProcessingTask($documentId, $filePath, $fileType, $content);
        
        $this->assertTrue(true);
    }

    /**
     * Тест публикации задачи генерации эмбеддинга
     */
    public function testPublishEmbeddingGenerationTask(): void
    {
        $chunkId = 'chunk-123';
        $chunkText = 'Test chunk text';
        $documentId = '123e4567-e89b-12d3-a456-426614174000';
        $chunkIndex = 1;

        $this->queueService->shouldReceive('publish')
            ->with('embedding.generation', Mockery::type('array'))
            ->once();

        $this->logger->shouldReceive('info')
            ->with('Embedding generation task published', [
                'chunk_id' => $chunkId,
                'document_id' => $documentId
            ])
            ->once();

        $this->taskService->publishEmbeddingGenerationTask($chunkId, $chunkText, $documentId, $chunkIndex);
        
        $this->assertTrue(true);
    }

    /**
     * Тест публикации уведомления пользователю
     */
    public function testPublishUserNotification(): void
    {
        $userId = 'user-123';
        $type = 'document_processed';
        $data = ['document_id' => 'doc-123', 'status' => 'completed'];

        $this->queueService->shouldReceive('publish')
            ->with('notification.user', Mockery::type('array'))
            ->once();

        $this->logger->shouldReceive('info')
            ->with('User notification published', [
                'user_id' => $userId,
                'notification_type' => $type
            ])
            ->once();

        $this->taskService->publishUserNotification($userId, $type, $data);
        
        $this->assertTrue(true);
    }

    /**
     * Тест получения статуса очереди
     */
    public function testGetQueueStatus(): void
    {
        $queueName = 'document.processing';
        $expectedStatus = ['pending' => 5, 'processing' => 2, 'completed' => 10];

        $this->queueService->shouldReceive('getQueueStatus')
            ->with($queueName)
            ->andReturn($expectedStatus)
            ->once();

        $result = $this->taskService->getQueueStatus($queueName);

        $this->assertEquals($expectedStatus, $result);
    }

    /**
     * Тест проверки соединения
     */
    public function testIsConnected(): void
    {
        $this->queueService->shouldReceive('isConnected')
            ->andReturn(true)
            ->once();

        $result = $this->taskService->isConnected();

        $this->assertTrue($result);
    }

    /**
     * Тест проверки соединения (отключено)
     */
    public function testIsNotConnected(): void
    {
        $this->queueService->shouldReceive('isConnected')
            ->andReturn(false)
            ->once();

        $result = $this->taskService->isConnected();

        $this->assertFalse($result);
    }

    /**
     * Тест с русскими символами
     */
    public function testPublishDocumentProcessingTaskWithRussianText(): void
    {
        $documentId = '123e4567-e89b-12d3-a456-426614174000';
        $filePath = '/path/to/документ.pdf';
        $fileType = 'application/pdf';
        $content = 'Тестовое содержимое документа';

        $this->queueService->shouldReceive('publish')
            ->with('document.processing', Mockery::type('array'))
            ->once();

        $this->logger->shouldReceive('info')
            ->with('Document processing task published', [
                'document_id' => $documentId,
                'file_type' => $fileType
            ])
            ->once();

        $this->taskService->publishDocumentProcessingTask($documentId, $filePath, $fileType, $content);
        
        $this->assertTrue(true);
    }

    /**
     * Тест структуры задачи обработки документа
     */
    public function testDocumentProcessingTaskStructure(): void
    {
        $documentId = '123e4567-e89b-12d3-a456-426614174000';
        $filePath = '/path/to/document.pdf';
        $fileType = 'application/pdf';
        $content = 'Test content';

        $this->queueService->shouldReceive('publish')
            ->with('document.processing', Mockery::on(function ($task) use ($documentId, $filePath, $fileType, $content) {
                return $task['type'] === 'document_processing' &&
                       $task['document_id'] === $documentId &&
                       $task['file_path'] === $filePath &&
                       $task['file_type'] === $fileType &&
                       $task['content'] === $content &&
                       $task['status'] === 'pending' &&
                       isset($task['created_at']) &&
                       is_int($task['created_at']);
            }))
            ->once();

        $this->logger->shouldReceive('info')->andReturnSelf();

        $this->taskService->publishDocumentProcessingTask($documentId, $filePath, $fileType, $content);
        
        $this->assertTrue(true);
    }

    /**
     * Тест структуры задачи генерации эмбеддинга
     */
    public function testEmbeddingGenerationTaskStructure(): void
    {
        $chunkId = 'chunk-123';
        $chunkText = 'Test chunk';
        $documentId = '123e4567-e89b-12d3-a456-426614174000';
        $chunkIndex = 5;

        $this->queueService->shouldReceive('publish')
            ->with('embedding.generation', Mockery::on(function ($task) use ($chunkId, $chunkText, $documentId, $chunkIndex) {
                return $task['type'] === 'embedding_generation' &&
                       $task['chunk_id'] === $chunkId &&
                       $task['chunk_text'] === $chunkText &&
                       $task['document_id'] === $documentId &&
                       $task['chunk_index'] === $chunkIndex &&
                       $task['status'] === 'pending' &&
                       isset($task['created_at']) &&
                       is_int($task['created_at']);
            }))
            ->once();

        $this->logger->shouldReceive('info')->andReturnSelf();

        $this->taskService->publishEmbeddingGenerationTask($chunkId, $chunkText, $documentId, $chunkIndex);
        
        $this->assertTrue(true);
    }

    /**
     * Тест структуры уведомления пользователю
     */
    public function testUserNotificationTaskStructure(): void
    {
        $userId = 'user-123';
        $type = 'document_processed';
        $data = ['key' => 'value'];

        $this->queueService->shouldReceive('publish')
            ->with('notification.user', Mockery::on(function ($task) use ($userId, $type, $data) {
                return $task['type'] === 'user_notification' &&
                       $task['user_id'] === $userId &&
                       $task['notification_type'] === $type &&
                       $task['data'] === $data &&
                       $task['status'] === 'pending' &&
                       isset($task['created_at']) &&
                       is_int($task['created_at']);
            }))
            ->once();

        $this->logger->shouldReceive('info')->andReturnSelf();

        $this->taskService->publishUserNotification($userId, $type, $data);
        
        $this->assertTrue(true);
    }
}
