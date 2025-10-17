<?php

declare(strict_types=1);

namespace RagSystem\Application\Service;

use RagSystem\Domain\Service\QueueServiceInterface;
use Psr\Log\LoggerInterface;

class TaskService
{
    public function __construct(
        private QueueServiceInterface $queueService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Публикует задачу обработки документа в очередь
     */
    public function publishDocumentProcessingTask(
        string $documentId,
        string $filePath,
        string $fileType,
        string $content
    ): void {
        $task = [
            'type' => 'document_processing',
            'document_id' => $documentId,
            'file_path' => $filePath,
            'file_type' => $fileType,
            'content' => $content,
            'created_at' => time(),
            'status' => 'pending'
        ];

        $this->queueService->publish('document.processing', $task);

        $this->logger->info('Document processing task published', [
            'document_id' => $documentId,
            'file_type' => $fileType
        ]);
    }

    /**
     * Публикует задачу генерации эмбеддинга для чанка
     */
    public function publishEmbeddingGenerationTask(
        string $chunkId,
        string $chunkText,
        string $documentId,
        int $chunkIndex
    ): void {
        $task = [
            'type' => 'embedding_generation',
            'chunk_id' => $chunkId,
            'chunk_text' => $chunkText,
            'document_id' => $documentId,
            'chunk_index' => $chunkIndex,
            'created_at' => time(),
            'status' => 'pending'
        ];

        $this->queueService->publish('embedding.generation', $task);

        $this->logger->info('Embedding generation task published', [
            'chunk_id' => $chunkId,
            'document_id' => $documentId
        ]);
    }

    /**
     * Публикует уведомление пользователю
     */
    public function publishUserNotification(
        string $userId,
        string $type,
        array $data
    ): void {
        $task = [
            'type' => 'user_notification',
            'user_id' => $userId,
            'notification_type' => $type,
            'data' => $data,
            'created_at' => time(),
            'status' => 'pending'
        ];

        $this->queueService->publish('notification.user', $task);

        $this->logger->info('User notification published', [
            'user_id' => $userId,
            'notification_type' => $type
        ]);
    }

    /**
     * Получает статус очереди
     */
    public function getQueueStatus(string $queueName): array
    {
        return $this->queueService->getQueueStatus($queueName);
    }

    /**
     * Проверяет соединение с брокером сообщений
     */
    public function isConnected(): bool
    {
        return $this->queueService->isConnected();
    }
}
