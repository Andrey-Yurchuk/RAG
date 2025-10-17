<?php

declare(strict_types=1);

namespace RagSystem\Domain\Service;

interface QueueServiceInterface
{
    /**
     * Публикует сообщение в очередь
     */
    public function publish(string $queueName, array $message): void;

    /**
     * Подписывается на очередь и обрабатывает сообщения
     */
    public function consume(string $queueName, callable $callback): void;

    /**
     * Получает статус очереди
     */
    public function getQueueStatus(string $queueName): array;

    /**
     * Проверяет соединение с брокером сообщений
     */
    public function isConnected(): bool;
}
