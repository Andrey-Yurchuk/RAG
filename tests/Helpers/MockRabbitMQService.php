<?php

declare(strict_types=1);

namespace RagSystem\Tests\Helpers;

use Exception;
use RagSystem\Domain\Service\QueueServiceInterface;
use RuntimeException;

class MockRabbitMQService implements QueueServiceInterface
{
    private array $queues = [];
    private array $publishedMessages = [];
    private bool $isConnected = true;
    private bool $shouldFail = false;
    private string $failureMessage = 'Mock queue failure';

    /**
     * Установка, должен ли сервис симулировать сбои
     */
    public function setShouldFail(bool $shouldFail, string $message = 'Mock queue failure'): void
    {
        $this->shouldFail = $shouldFail;
        $this->failureMessage = $message;
    }

    /**
     * Установка статуса подключения
     */
    public function setConnected(bool $isConnected): void
    {
        $this->isConnected = $isConnected;
    }

    /**
     * Мок метода publish
     */
    public function publish(string $queueName, array $message): void
    {
        if ($this->shouldFail) {
            throw new RuntimeException($this->failureMessage);
        }

        if (!$this->isConnected) {
            throw new RuntimeException('Queue service not connected');
        }

        if (!isset($this->publishedMessages[$queueName])) {
            $this->publishedMessages[$queueName] = [];
        }
        
        $this->publishedMessages[$queueName][] = $message;

        if (!isset($this->queues[$queueName])) {
            $this->queues[$queueName] = [
                'name' => $queueName,
                'message_count' => 0,
                'consumer_count' => 0,
                'status' => 'active'
            ];
        }
        
        $this->queues[$queueName]['message_count']++;
    }

    /**
     * Мок метода consume
     */
    public function consume(string $queueName, callable $callback): void
    {
        if ($this->shouldFail) {
            throw new RuntimeException($this->failureMessage);
        }

        if (!$this->isConnected) {
            throw new RuntimeException('Queue service not connected');
        }

        if (isset($this->publishedMessages[$queueName])) {
            foreach ($this->publishedMessages[$queueName] as $message) {
                try {
                    $callback($message);
                } catch (Exception $e) {
                    error_log("Error processing message: " . $e->getMessage());
                }
            }

            $this->publishedMessages[$queueName] = [];
            $this->queues[$queueName]['message_count'] = 0;
        }
    }

    /**
     * Мок метода getQueueStatus
     */
    public function getQueueStatus(string $queueName): array
    {
        if ($this->shouldFail) {
            throw new RuntimeException($this->failureMessage);
        }

        if (!isset($this->queues[$queueName])) {
            return [
                'name' => $queueName,
                'message_count' => 0,
                'consumer_count' => 0,
                'status' => 'not_found'
            ];
        }

        return $this->queues[$queueName];
    }

    /**
     * Мок метода isConnected
     */
    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    /**
     * Получение всех опубликованных сообщений для тестирования
     */
    public function getPublishedMessages(?string $queueName = null): array
    {
        if ($queueName !== null) {
            return $this->publishedMessages[$queueName] ?? [];
        }
        
        return $this->publishedMessages;
    }

    /**
     * Очистка всех опубликованных сообщений
     */
    public function clearPublishedMessages(): void
    {
        $this->publishedMessages = [];
        $this->queues = [];
    }

    /**
     * Симуляция сбоя очереди
     */
    public function simulateFailure(string $message = 'Simulated queue failure'): void
    {
        $this->setShouldFail(true, $message);
    }

    /**
     * Сброс мока в начальное состояние
     */
    public function reset(): void
    {
        $this->queues = [];
        $this->publishedMessages = [];
        $this->isConnected = true;
        $this->shouldFail = false;
        $this->failureMessage = 'Mock queue failure';
    }

    /**
     * Получение статистики о мок-сервисе
     */
    public function getStats(): array
    {
        $totalMessages = 0;
        foreach ($this->publishedMessages as $messages) {
            $totalMessages += count($messages);
        }

        return [
            'total_queues' => count($this->queues),
            'total_published_messages' => $totalMessages,
            'is_connected' => $this->isConnected,
            'should_fail' => $this->shouldFail,
        ];
    }
}
